import { defineContentScript } from 'wxt/sandbox';
import { authStorage, campaignStorage } from '../../utils/storage';
import type { Campaign, Lead } from '../../types';

export default defineContentScript({
  matches: ['*://*.upwork.com/*'],
  main() {
    console.log('EvenLeads: Upwork content script loaded');

    // Initialize Upwork monitoring
    initUpworkMonitoring();

    // Watch for navigation changes (Upwork is a SPA)
    let lastUrl = location.href;
    new MutationObserver(() => {
      const url = location.href;
      if (url !== lastUrl) {
        lastUrl = url;
        initUpworkMonitoring();
      }
    }).observe(document, { subtree: true, childList: true });
  },
});

async function initUpworkMonitoring() {
  const isAuth = await authStorage.isAuthenticated();
  if (!isAuth) {
    console.log('EvenLeads: Not authenticated, skipping monitoring');
    return;
  }

  // Get selected campaign or all Upwork campaigns
  const selectedCampaign = await campaignStorage.getSelectedCampaign();
  const allCampaigns = await campaignStorage.get();

  const upworkCampaigns = selectedCampaign
    ? [selectedCampaign].filter(c => c.platforms.includes('upwork'))
    : allCampaigns.filter(c => c.platforms.includes('upwork'));

  if (upworkCampaigns.length === 0) {
    console.log('EvenLeads: No Upwork campaigns active');
    return;
  }

  console.log(`EvenLeads: Monitoring Upwork for ${upworkCampaigns.length} campaign(s)`);

  // Detect page type and initialize appropriate monitoring
  const currentUrl = window.location.href;

  if (currentUrl.includes('/search/jobs') || currentUrl.includes('/jobs/search')) {
    // Job search results
    monitorJobSearch(upworkCampaigns);
  } else if (currentUrl.includes('/jobs/') && currentUrl.includes('~')) {
    // Single job page
    monitorSingleJob(upworkCampaigns);
  } else if (currentUrl.includes('/nx/find-work')) {
    // Find work page (for freelancers)
    monitorJobSearch(upworkCampaigns);
  }
}

function monitorJobSearch(campaigns: Campaign[]) {
  console.log('EvenLeads: Monitoring Upwork job search');
  scanJobs(campaigns);
  watchForNewJobs(campaigns);
}

function monitorSingleJob(campaigns: Campaign[]) {
  console.log('EvenLeads: Monitoring single Upwork job');
  scanDetailedJob(campaigns);
}

function scanJobs(campaigns: Campaign[]) {
  // Upwork job card selectors
  const jobCards = document.querySelectorAll(
    '[data-test="job-tile-list"] article, .job-tile, section[data-ev-sublocation="search_results"] article, .up-card-section'
  );

  console.log(`EvenLeads: Found ${jobCards.length} jobs to scan`);

  jobCards.forEach((card) => {
    try {
      // Get job title
      const titleElement = card.querySelector('h2 a, h3 a, [data-test="UpCLineClamp JobTitle"] a, .job-title-link');
      const title = titleElement?.textContent?.trim() || '';

      // Get job description
      const descElement = card.querySelector('[data-test="UpCLineClamp JobDescription"], .job-description, .up-line-clamp-v2');
      const description = descElement?.textContent?.trim() || '';

      // Get job URL
      const linkElement = card.querySelector('a[href*="/jobs/"]') || titleElement;
      const href = linkElement?.getAttribute('href') || '';
      const jobUrl = href.startsWith('http') ? href : `https://www.upwork.com${href}`;

      // Extract job ID from URL
      const jobId = href.match(/jobs\/~([0-9a-f]+)/)?.[1] || `upwork_${Date.now()}`;

      // Get client information
      const clientElement = card.querySelector('[data-test="client-country"], .client-location, .text-light');
      const clientInfo = clientElement?.textContent?.trim() || '';

      // Get budget/payment info
      const budgetElement = card.querySelector('[data-test="is-fixed-price"], [data-test="JobInfoClient BudgetAmount"], .job-type-label, strong');
      const budget = budgetElement?.textContent?.trim() || '';

      // Get posting time
      const timeElement = card.querySelector('[data-test="JobInfoClient PostedOn"], time, .posted-on');
      const postedTime = timeElement?.textContent?.trim() || '';

      // Get skills/tags
      const skillElements = card.querySelectorAll('[data-test="TokenClamp"], .up-skill-badge, .skill-tag');
      const skills = Array.from(skillElements).map(el => el.textContent?.trim() || '').filter(Boolean);

      // Get level/experience required
      const levelElement = card.querySelector('[data-test="experience-level"], .contractor-tier');
      const level = levelElement?.textContent?.trim() || '';

      // Get proposals count
      const proposalsElement = card.querySelector('[data-test="proposals"], .proposals-count');
      const proposals = proposalsElement?.textContent?.trim() || '';

      const fullText = `${title} ${description} ${skills.join(' ')} ${level}`.toLowerCase();

      // Check each campaign
      campaigns.forEach((campaign) => {
        const matchedKeywords = campaign.keywords.filter((keyword) =>
          fullText.includes(keyword.toLowerCase())
        );

        if (matchedKeywords.length > 0) {
          const lead: Partial<Lead> = {
            platform: 'upwork',
            platform_id: jobId,
            title: title || 'Upwork Job',
            description: `${description}\n\nBudget: ${budget}\nLevel: ${level}\nProposals: ${proposals}\nClient: ${clientInfo}\nPosted: ${postedTime}\nSkills: ${skills.join(', ')}`.substring(0, 1000),
            url: jobUrl,
            author: clientInfo || 'Upwork Client',
            matched_keywords: matchedKeywords,
            confidence_score: calculateConfidence(matchedKeywords, campaign.keywords),
            upwork_job_id: jobId,
          };

          submitLead(campaign.id, lead);
        }
      });
    } catch (error) {
      console.error('EvenLeads: Error scanning job', error);
    }
  });
}

function scanDetailedJob(campaigns: Campaign[]) {
  try {
    // Extract detailed information from single job page
    const titleElement = document.querySelector('h2[itemprop="title"], h4.m-0');
    const title = titleElement?.textContent?.trim() || '';

    const descElement = document.querySelector('[itemprop="description"], .break, .mb-20 .text-body-sm');
    const description = descElement?.textContent?.trim() || '';

    // Get URL and job ID
    const jobUrl = window.location.href;
    const jobId = jobUrl.match(/jobs\/~([0-9a-f]+)/)?.[1] || `upwork_${Date.now()}`;

    // Get client information
    const clientElement = document.querySelector('.client-about, .up-client-info');
    const clientInfo = clientElement?.textContent?.trim() || '';

    // Get budget
    const budgetElement = document.querySelector('[itemprop="baseSalary"], .budget strong');
    const budget = budgetElement?.textContent?.trim() || '';

    // Get skills
    const skillElements = document.querySelectorAll('[data-test="token"], .up-skill-badge');
    const skills = Array.from(skillElements).map(el => el.textContent?.trim() || '').filter(Boolean);

    // Get project details
    const detailsElements = document.querySelectorAll('.job-details li, .detail-item');
    const details = Array.from(detailsElements).map(el => el.textContent?.trim() || '').filter(Boolean);

    const fullText = `${title} ${description} ${skills.join(' ')} ${details.join(' ')}`.toLowerCase();

    // Check each campaign
    campaigns.forEach((campaign) => {
      const matchedKeywords = campaign.keywords.filter((keyword) =>
        fullText.includes(keyword.toLowerCase())
      );

      if (matchedKeywords.length > 0) {
        const lead: Partial<Lead> = {
          platform: 'upwork',
          platform_id: jobId,
          title: title || 'Upwork Job',
          description: `${description}\n\nBudget: ${budget}\nClient: ${clientInfo}\nSkills: ${skills.join(', ')}\n\n${details.join('\n')}`.substring(0, 1000),
          url: jobUrl,
          author: 'Upwork Client',
          matched_keywords: matchedKeywords,
          confidence_score: calculateConfidence(matchedKeywords, campaign.keywords),
          upwork_job_id: jobId,
        };

        submitLead(campaign.id, lead);
      }
    });
  } catch (error) {
    console.error('EvenLeads: Error scanning detailed job', error);
  }
}

function watchForNewJobs(campaigns: Campaign[]) {
  const observer = new MutationObserver((mutations) => {
    let hasNewJobs = false;

    mutations.forEach((mutation) => {
      mutation.addedNodes.forEach((node) => {
        if (node.nodeType === Node.ELEMENT_NODE) {
          const element = node as Element;
          if (
            element.classList.contains('job-tile') ||
            element.classList.contains('up-card-section') ||
            element.tagName.toLowerCase() === 'article'
          ) {
            hasNewJobs = true;
          }
        }
      });
    });

    if (hasNewJobs) {
      setTimeout(() => scanJobs(campaigns), 1000);
    }
  });

  observer.observe(document.body, {
    childList: true,
    subtree: true,
  });
}

function calculateConfidence(matchedKeywords: string[], allKeywords: string[]): number {
  const matchRatio = matchedKeywords.length / allKeywords.length;
  return Math.min(Math.round(matchRatio * 10) + 5, 10);
}

async function submitLead(campaignId: number, lead: Partial<Lead>) {
  try {
    const response = await chrome.runtime.sendMessage({
      type: 'SUBMIT_LEAD',
      payload: { campaignId, lead },
    });

    if (response?.error) {
      console.error('EvenLeads: Failed to submit lead:', response.error);
    } else {
      console.log('EvenLeads: Lead submitted successfully', lead);
      showLeadDetectedNotification(lead.title || 'New lead detected');
    }
  } catch (error) {
    console.error('EvenLeads: Failed to submit lead', error);
  }
}

function showLeadDetectedNotification(title: string) {
  const notification = document.createElement('div');
  notification.style.cssText = `
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 10000;
    padding: 12px 16px;
    background: linear-gradient(135deg, #14A800 0%, #0F8A00 100%);
    color: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    font-size: 13px;
    font-weight: 500;
    max-width: 300px;
    animation: slideIn 0.3s ease-out;
  `;

  notification.innerHTML = `
    <div style="display: flex; align-items: center; gap: 8px;">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
        <polyline points="22 4 12 14.01 9 11.01"></polyline>
      </svg>
      <span>Lead detected: ${title.substring(0, 50)}${title.length > 50 ? '...' : ''}</span>
    </div>
  `;

  document.body.appendChild(notification);

  setTimeout(() => {
    notification.style.animation = 'slideOut 0.3s ease-out';
    setTimeout(() => notification.remove(), 300);
  }, 3000);
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
  @keyframes slideIn {
    from {
      transform: translateX(400px);
      opacity: 0;
    }
    to {
      transform: translateX(0);
      opacity: 1;
    }
  }
  @keyframes slideOut {
    from {
      transform: translateX(0);
      opacity: 1;
    }
    to {
      transform: translateX(400px);
      opacity: 0;
    }
  }
`;
document.head.appendChild(style);
