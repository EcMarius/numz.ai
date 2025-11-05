const { createCanvas, loadImage } = require('canvas');
const fs = require('fs');
const path = require('path');

const sizes = [16, 32, 48, 128];
const outputDir = path.join(__dirname, '../public/icon');
const sourceIcon = path.join(__dirname, '../public/favicon-inverted.png');

// Create output directory if it doesn't exist
if (!fs.existsSync(outputDir)) {
  fs.mkdirSync(outputDir, { recursive: true });
}

async function resizeIcon(size, image) {
  const canvas = createCanvas(size, size);
  const ctx = canvas.getContext('2d');

  // Draw the image scaled to the target size
  ctx.drawImage(image, 0, 0, size, size);

  return canvas;
}

console.log('🎨 Generating EvenLeads extension icons from favicon-inverted.png...\n');

(async () => {
  try {
    // Load the source icon
    const image = await loadImage(sourceIcon);
    console.log(`✓ Loaded source icon: favicon-inverted.png\n`);

    // Generate all sizes
    for (const size of sizes) {
      const canvas = await resizeIcon(size, image);
      const buffer = canvas.toBuffer('image/png');
      const filename = `${size}.png`;
      const filepath = path.join(outputDir, filename);

      fs.writeFileSync(filepath, buffer);
      console.log(`✓ Generated ${filename} (${size}x${size})`);
    }

    console.log('\n✅ All icons generated successfully!');
    console.log(`📁 Icons saved to: ${outputDir}\n`);
  } catch (error) {
    console.error('❌ Error generating icons:', error.message);
    process.exit(1);
  }
})();
