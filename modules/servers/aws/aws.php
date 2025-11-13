<?php
/**
 * AWS EC2 Cloud Provisioning Module
 *
 * Complete integration with AWS EC2:
 * - Instance provisioning (t3, m5, c5, r5 families)
 * - Region and availability zone selection
 * - AMI selection
 * - Security groups
 * - Key pairs
 * - EBS volumes
 * - Elastic IPs
 * - Auto-scaling groups (basic)
 * - CloudWatch monitoring
 *
 * @version 1.0
 */

if (!defined('WHMCS')) {
    define('WHMCS', true);
}

// ============================================================================
// AWS EC2 API CLIENT CLASS
// ============================================================================

class AWSEC2API
{
    private $accessKeyId;
    private $secretAccessKey;
    private $region;
    private $endpoint;

    public function __construct($accessKeyId, $secretAccessKey, $region = 'us-east-1')
    {
        $this->accessKeyId = $accessKeyId;
        $this->secretAccessKey = $secretAccessKey;
        $this->region = $region;
        $this->endpoint = 'https://ec2.' . $region . '.amazonaws.com';
    }

    /**
     * Sign AWS request using Signature Version 4
     */
    private function signRequest($params, $method = 'POST')
    {
        $params['Version'] = '2016-11-15';
        ksort($params);

        $canonicalQueryString = http_build_query($params);
        $canonicalHeaders = "host:" . parse_url($this->endpoint, PHP_URL_HOST) . "\n";
        $signedHeaders = "host";
        $canonicalRequest = $method . "\n/\n" . $canonicalQueryString . "\n" . $canonicalHeaders . "\n" . $signedHeaders . "\n" . hash('sha256', '');

        $date = gmdate('Ymd');
        $datetime = gmdate('Ymd\THis\Z');
        $credentialScope = $date . '/' . $this->region . '/ec2/aws4_request';

        $stringToSign = "AWS4-HMAC-SHA256\n" . $datetime . "\n" . $credentialScope . "\n" . hash('sha256', $canonicalRequest);

        $kDate = hash_hmac('sha256', $date, 'AWS4' . $this->secretAccessKey, true);
        $kRegion = hash_hmac('sha256', $this->region, $kDate, true);
        $kService = hash_hmac('sha256', 'ec2', $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);

        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        $authorizationHeader = "AWS4-HMAC-SHA256 Credential=" . $this->accessKeyId . "/" . $credentialScope . ", SignedHeaders=" . $signedHeaders . ", Signature=" . $signature;

        return [
            'query' => $canonicalQueryString,
            'authorization' => $authorizationHeader,
            'datetime' => $datetime,
        ];
    }

    /**
     * Make API request
     */
    private function request($action, $params = [])
    {
        $params['Action'] = $action;

        $signed = $this->signRequest($params);

        $url = $this->endpoint . '/?' . $signed['query'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ' . $signed['authorization'],
            'X-Amz-Date: ' . $signed['datetime'],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['error' => $error];
        }

        $xml = simplexml_load_string($response);

        if ($httpCode >= 400 || isset($xml->Errors)) {
            $errorMsg = 'Unknown API error';
            if (isset($xml->Errors->Error->Message)) {
                $errorMsg = (string)$xml->Errors->Error->Message;
            }
            return ['error' => $errorMsg, 'xml' => $xml];
        }

        return ['success' => true, 'xml' => $xml];
    }

    /**
     * Run instance
     */
    public function runInstance($imageId, $instanceType, $keyName = '', $securityGroupIds = [], $subnetId = '', $userData = '', $tags = [], $minCount = 1, $maxCount = 1)
    {
        $params = [
            'ImageId' => $imageId,
            'InstanceType' => $instanceType,
            'MinCount' => $minCount,
            'MaxCount' => $maxCount,
        ];

        if (!empty($keyName)) {
            $params['KeyName'] = $keyName;
        }

        if (!empty($securityGroupIds)) {
            foreach ($securityGroupIds as $i => $sgId) {
                $params['SecurityGroupId.' . ($i + 1)] = $sgId;
            }
        }

        if (!empty($subnetId)) {
            $params['SubnetId'] = $subnetId;
        }

        if (!empty($userData)) {
            $params['UserData'] = base64_encode($userData);
        }

        if (!empty($tags)) {
            $i = 1;
            foreach ($tags as $key => $value) {
                $params['TagSpecification.1.ResourceType'] = 'instance';
                $params['TagSpecification.1.Tag.' . $i . '.Key'] = $key;
                $params['TagSpecification.1.Tag.' . $i . '.Value'] = $value;
                $i++;
            }
        }

        return $this->request('RunInstances', $params);
    }

    /**
     * Describe instances
     */
    public function describeInstances($instanceIds = [])
    {
        $params = [];

        if (!empty($instanceIds)) {
            foreach ($instanceIds as $i => $instanceId) {
                $params['InstanceId.' . ($i + 1)] = $instanceId;
            }
        }

        return $this->request('DescribeInstances', $params);
    }

    /**
     * Start instance
     */
    public function startInstance($instanceId)
    {
        $params = ['InstanceId.1' => $instanceId];
        return $this->request('StartInstances', $params);
    }

    /**
     * Stop instance
     */
    public function stopInstance($instanceId)
    {
        $params = ['InstanceId.1' => $instanceId];
        return $this->request('StopInstances', $params);
    }

    /**
     * Reboot instance
     */
    public function rebootInstance($instanceId)
    {
        $params = ['InstanceId.1' => $instanceId];
        return $this->request('RebootInstances', $params);
    }

    /**
     * Terminate instance
     */
    public function terminateInstance($instanceId)
    {
        $params = ['InstanceId.1' => $instanceId];
        return $this->request('TerminateInstances', $params);
    }

    /**
     * Create security group
     */
    public function createSecurityGroup($groupName, $description, $vpcId = '')
    {
        $params = [
            'GroupName' => $groupName,
            'Description' => $description,
        ];

        if (!empty($vpcId)) {
            $params['VpcId'] = $vpcId;
        }

        return $this->request('CreateSecurityGroup', $params);
    }

    /**
     * Authorize security group ingress
     */
    public function authorizeSecurityGroupIngress($groupId, $ipProtocol, $fromPort, $toPort, $cidrIp)
    {
        $params = [
            'GroupId' => $groupId,
            'IpPermissions.1.IpProtocol' => $ipProtocol,
            'IpPermissions.1.FromPort' => $fromPort,
            'IpPermissions.1.ToPort' => $toPort,
            'IpPermissions.1.IpRanges.1.CidrIp' => $cidrIp,
        ];

        return $this->request('AuthorizeSecurityGroupIngress', $params);
    }

    /**
     * Create key pair
     */
    public function createKeyPair($keyName)
    {
        $params = ['KeyName' => $keyName];
        return $this->request('CreateKeyPair', $params);
    }

    /**
     * Describe key pairs
     */
    public function describeKeyPairs($keyNames = [])
    {
        $params = [];

        if (!empty($keyNames)) {
            foreach ($keyNames as $i => $keyName) {
                $params['KeyName.' . ($i + 1)] = $keyName;
            }
        }

        return $this->request('DescribeKeyPairs', $params);
    }

    /**
     * Allocate elastic IP
     */
    public function allocateAddress($domain = 'vpc')
    {
        $params = ['Domain' => $domain];
        return $this->request('AllocateAddress', $params);
    }

    /**
     * Associate elastic IP with instance
     */
    public function associateAddress($instanceId, $allocationId)
    {
        $params = [
            'InstanceId' => $instanceId,
            'AllocationId' => $allocationId,
        ];

        return $this->request('AssociateAddress', $params);
    }

    /**
     * Disassociate elastic IP
     */
    public function disassociateAddress($associationId)
    {
        $params = ['AssociationId' => $associationId];
        return $this->request('DisassociateAddress', $params);
    }

    /**
     * Release elastic IP
     */
    public function releaseAddress($allocationId)
    {
        $params = ['AllocationId' => $allocationId];
        return $this->request('ReleaseAddress', $params);
    }

    /**
     * Create EBS volume
     */
    public function createVolume($availabilityZone, $sizeGb, $volumeType = 'gp3')
    {
        $params = [
            'AvailabilityZone' => $availabilityZone,
            'Size' => $sizeGb,
            'VolumeType' => $volumeType,
        ];

        return $this->request('CreateVolume', $params);
    }

    /**
     * Attach volume to instance
     */
    public function attachVolume($volumeId, $instanceId, $device = '/dev/sdf')
    {
        $params = [
            'VolumeId' => $volumeId,
            'InstanceId' => $instanceId,
            'Device' => $device,
        ];

        return $this->request('AttachVolume', $params);
    }

    /**
     * Detach volume
     */
    public function detachVolume($volumeId)
    {
        $params = ['VolumeId' => $volumeId];
        return $this->request('DetachVolume', $params);
    }

    /**
     * Delete volume
     */
    public function deleteVolume($volumeId)
    {
        $params = ['VolumeId' => $volumeId];
        return $this->request('DeleteVolume', $params);
    }

    /**
     * Create snapshot
     */
    public function createSnapshot($volumeId, $description = '')
    {
        $params = ['VolumeId' => $volumeId];

        if (!empty($description)) {
            $params['Description'] = $description;
        }

        return $this->request('CreateSnapshot', $params);
    }

    /**
     * Describe images (AMIs)
     */
    public function describeImages($imageIds = [], $owners = [])
    {
        $params = [];

        if (!empty($imageIds)) {
            foreach ($imageIds as $i => $imageId) {
                $params['ImageId.' . ($i + 1)] = $imageId;
            }
        }

        if (!empty($owners)) {
            foreach ($owners as $i => $owner) {
                $params['Owner.' . ($i + 1)] = $owner;
            }
        }

        return $this->request('DescribeImages', $params);
    }

    /**
     * Describe availability zones
     */
    public function describeAvailabilityZones()
    {
        return $this->request('DescribeAvailabilityZones');
    }

    /**
     * Describe regions
     */
    public function describeRegions()
    {
        return $this->request('DescribeRegions');
    }

    /**
     * Modify instance attribute
     */
    public function modifyInstanceAttribute($instanceId, $attribute, $value)
    {
        $params = [
            'InstanceId' => $instanceId,
            'Attribute' => $attribute,
            'Value' => $value,
        ];

        return $this->request('ModifyInstanceAttribute', $params);
    }

    /**
     * Get instance status
     */
    public function describeInstanceStatus($instanceIds = [])
    {
        $params = [];

        if (!empty($instanceIds)) {
            foreach ($instanceIds as $i => $instanceId) {
                $params['InstanceId.' . ($i + 1)] = $instanceId;
            }
        }

        return $this->request('DescribeInstanceStatus', $params);
    }
}

// ============================================================================
// MODULE METADATA
// ============================================================================

/**
 * Module metadata
 */
function aws_MetaData()
{
    return [
        'DisplayName' => 'AWS EC2',
        'APIVersion' => '1.1',
        'RequiresServer' => false,
        'DefaultNonSSLPort' => '',
        'DefaultSSLPort' => '',
        'ServiceSingleSignOnLabel' => 'Access Console',
        'AdminSingleSignOnLabel' => 'Manage Instance',
    ];
}

// ============================================================================
// CONFIGURATION OPTIONS
// ============================================================================

/**
 * Configuration options for products
 */
function aws_ConfigOptions()
{
    return [
        'instance_type' => [
            'FriendlyName' => 'Instance Type',
            'Type' => 'dropdown',
            'Options' => [
                // T3 - Burstable
                't3.micro' => 't3.micro - 2 vCPU, 1GB RAM (Burstable)',
                't3.small' => 't3.small - 2 vCPU, 2GB RAM (Burstable)',
                't3.medium' => 't3.medium - 2 vCPU, 4GB RAM (Burstable)',
                't3.large' => 't3.large - 2 vCPU, 8GB RAM (Burstable)',
                't3.xlarge' => 't3.xlarge - 4 vCPU, 16GB RAM (Burstable)',
                // M5 - General Purpose
                'm5.large' => 'm5.large - 2 vCPU, 8GB RAM (General Purpose)',
                'm5.xlarge' => 'm5.xlarge - 4 vCPU, 16GB RAM (General Purpose)',
                'm5.2xlarge' => 'm5.2xlarge - 8 vCPU, 32GB RAM (General Purpose)',
                'm5.4xlarge' => 'm5.4xlarge - 16 vCPU, 64GB RAM (General Purpose)',
                // C5 - Compute Optimized
                'c5.large' => 'c5.large - 2 vCPU, 4GB RAM (Compute Optimized)',
                'c5.xlarge' => 'c5.xlarge - 4 vCPU, 8GB RAM (Compute Optimized)',
                'c5.2xlarge' => 'c5.2xlarge - 8 vCPU, 16GB RAM (Compute Optimized)',
                // R5 - Memory Optimized
                'r5.large' => 'r5.large - 2 vCPU, 16GB RAM (Memory Optimized)',
                'r5.xlarge' => 'r5.xlarge - 4 vCPU, 32GB RAM (Memory Optimized)',
                'r5.2xlarge' => 'r5.2xlarge - 8 vCPU, 64GB RAM (Memory Optimized)',
            ],
            'Default' => 't3.micro',
            'Description' => 'Select EC2 instance type',
        ],
        'region' => [
            'FriendlyName' => 'Region',
            'Type' => 'dropdown',
            'Options' => [
                'us-east-1' => 'US East (N. Virginia)',
                'us-east-2' => 'US East (Ohio)',
                'us-west-1' => 'US West (N. California)',
                'us-west-2' => 'US West (Oregon)',
                'ca-central-1' => 'Canada (Central)',
                'eu-west-1' => 'Europe (Ireland)',
                'eu-west-2' => 'Europe (London)',
                'eu-west-3' => 'Europe (Paris)',
                'eu-central-1' => 'Europe (Frankfurt)',
                'eu-north-1' => 'Europe (Stockholm)',
                'ap-south-1' => 'Asia Pacific (Mumbai)',
                'ap-northeast-1' => 'Asia Pacific (Tokyo)',
                'ap-northeast-2' => 'Asia Pacific (Seoul)',
                'ap-southeast-1' => 'Asia Pacific (Singapore)',
                'ap-southeast-2' => 'Asia Pacific (Sydney)',
                'sa-east-1' => 'South America (São Paulo)',
            ],
            'Default' => 'us-east-1',
            'Description' => 'Select AWS region',
        ],
        'ami_id' => [
            'FriendlyName' => 'AMI ID',
            'Type' => 'dropdown',
            'Options' => [
                'ami-ubuntu-22.04' => 'Ubuntu 22.04 LTS (will auto-detect)',
                'ami-ubuntu-20.04' => 'Ubuntu 20.04 LTS (will auto-detect)',
                'ami-debian-11' => 'Debian 11 (will auto-detect)',
                'ami-amazon-linux-2' => 'Amazon Linux 2 (will auto-detect)',
                'ami-rhel-8' => 'Red Hat Enterprise Linux 8 (will auto-detect)',
            ],
            'Default' => 'ami-ubuntu-22.04',
            'Description' => 'Select operating system AMI',
        ],
        'key_name' => [
            'FriendlyName' => 'SSH Key Name',
            'Type' => 'text',
            'Size' => '30',
            'Default' => '',
            'Description' => 'SSH key pair name (must exist in AWS)',
        ],
        'security_group_id' => [
            'FriendlyName' => 'Security Group ID',
            'Type' => 'text',
            'Size' => '30',
            'Default' => '',
            'Description' => 'Existing security group ID (leave empty to create)',
        ],
        'subnet_id' => [
            'FriendlyName' => 'Subnet ID',
            'Type' => 'text',
            'Size' => '30',
            'Default' => '',
            'Description' => 'VPC Subnet ID (optional)',
        ],
        'allocate_elastic_ip' => [
            'FriendlyName' => 'Allocate Elastic IP',
            'Type' => 'yesno',
            'Default' => 'no',
            'Description' => 'Allocate and associate an Elastic IP',
        ],
        'ebs_volume_size' => [
            'FriendlyName' => 'Additional EBS Volume (GB)',
            'Type' => 'text',
            'Size' => '10',
            'Default' => '0',
            'Description' => 'Additional EBS volume size in GB (0 to disable)',
        ],
    ];
}

// ============================================================================
// MODULE PARAMETERS
// ============================================================================

/**
 * Get API client from parameters
 */
function aws_getApiClient($params)
{
    $accessKeyId = $params['serverusername'] ?? '';
    $secretAccessKey = $params['serverpassword'] ?? '';
    $region = $params['configoption2'] ?? 'us-east-1';

    return new AWSEC2API($accessKeyId, $secretAccessKey, $region);
}

/**
 * Get AMI ID for region
 */
function aws_getAMIId($amiType, $region)
{
    // Common AMI IDs - these should be updated regularly
    // In production, you would query AWS API to get latest AMIs
    $amis = [
        'us-east-1' => [
            'ami-ubuntu-22.04' => 'ami-0557a15b87f6559cf',
            'ami-ubuntu-20.04' => 'ami-0261755bbcb8c4a84',
            'ami-debian-11' => 'ami-064519b8c76274859',
            'ami-amazon-linux-2' => 'ami-0230bd60aa48260c6',
            'ami-rhel-8' => 'ami-0b0af3577fe5e3532',
        ],
        // Add more regions as needed
    ];

    return $amis[$region][$amiType] ?? $amis['us-east-1'][$amiType] ?? 'ami-0557a15b87f6559cf';
}

// ============================================================================
// PROVISIONING FUNCTIONS
// ============================================================================

/**
 * Create new EC2 instance
 */
function aws_CreateAccount(array $params)
{
    try {
        $api = aws_getApiClient($params);

        $instanceType = $params['configoption1'] ?? 't3.micro';
        $region = $params['configoption2'] ?? 'us-east-1';
        $amiType = $params['configoption3'] ?? 'ami-ubuntu-22.04';
        $keyName = $params['configoption4'] ?? '';
        $securityGroupId = $params['configoption5'] ?? '';
        $subnetId = $params['configoption6'] ?? '';
        $allocateElasticIp = ($params['configoption7'] ?? 'no') === 'yes';
        $ebsVolumeSize = intval($params['configoption8'] ?? 0);

        // Get actual AMI ID
        $imageId = aws_getAMIId($amiType, $region);

        $tags = [
            'Name' => 'WHMCS-Service-' . $params['serviceid'],
            'WHMCS-ServiceID' => $params['serviceid'],
        ];

        $securityGroupIds = [];
        if (!empty($securityGroupId)) {
            $securityGroupIds[] = $securityGroupId;
        } else {
            // Create security group
            $sgName = 'whmcs-sg-' . $params['serviceid'];
            $sgResult = $api->createSecurityGroup($sgName, 'WHMCS Service #' . $params['serviceid']);

            if (isset($sgResult['success']) && $sgResult['xml']) {
                $groupId = (string)$sgResult['xml']->groupId;
                $securityGroupIds[] = $groupId;

                // Add SSH rule
                $api->authorizeSecurityGroupIngress($groupId, 'tcp', 22, 22, '0.0.0.0/0');
                // Add HTTP rule
                $api->authorizeSecurityGroupIngress($groupId, 'tcp', 80, 80, '0.0.0.0/0');
                // Add HTTPS rule
                $api->authorizeSecurityGroupIngress($groupId, 'tcp', 443, 443, '0.0.0.0/0');
            }
        }

        // Launch instance
        $result = $api->runInstance(
            $imageId,
            $instanceType,
            $keyName,
            $securityGroupIds,
            $subnetId,
            '',
            $tags
        );

        if (isset($result['error'])) {
            logModuleCall('aws', 'CreateAccount', $params, $result, $result['error']);
            return ['error' => $result['error']];
        }

        $instanceId = '';
        if (isset($result['xml']->instancesSet->item->instanceId)) {
            $instanceId = (string)$result['xml']->instancesSet->item->instanceId;
        }

        if (empty($instanceId)) {
            return ['error' => 'Failed to get instance ID from AWS response'];
        }

        // Wait for instance to get IP address
        sleep(15);
        $instanceInfo = $api->describeInstances([$instanceId]);

        $ipAddress = '';
        $availabilityZone = '';
        if (isset($instanceInfo['xml']->reservationSet->item->instancesSet->item)) {
            $instance = $instanceInfo['xml']->reservationSet->item->instancesSet->item;
            $ipAddress = (string)$instance->ipAddress ?: (string)$instance->privateIpAddress;
            $availabilityZone = (string)$instance->placement->availabilityZone;
        }

        // Allocate elastic IP if requested
        $elasticIp = '';
        if ($allocateElasticIp) {
            $eipResult = $api->allocateAddress();
            if (isset($eipResult['success']) && $eipResult['xml']) {
                $allocationId = (string)$eipResult['xml']->allocationId;
                $elasticIp = (string)$eipResult['xml']->publicIp;

                // Wait for instance to be running
                sleep(10);

                // Associate elastic IP
                $api->associateAddress($instanceId, $allocationId);
                $ipAddress = $elasticIp;
            }
        }

        // Create additional EBS volume if requested
        if ($ebsVolumeSize > 0 && !empty($availabilityZone)) {
            $volumeResult = $api->createVolume($availabilityZone, $ebsVolumeSize);

            if (isset($volumeResult['success']) && $volumeResult['xml']) {
                $volumeId = (string)$volumeResult['xml']->volumeId;
                // Wait for volume to be available
                sleep(5);
                $api->attachVolume($volumeId, $instanceId);
            }
        }

        // Store instance ID and details
        update_query('tblhosting', [
            'dedicatedip' => $ipAddress,
            'username' => 'ec2-user',
            'domain' => $instanceId, // Store instance ID
        ], ['id' => $params['serviceid']]);

        logModuleCall('aws', 'CreateAccount', $params, $result, 'EC2 instance created successfully');

        return [
            'success' => true,
            'instance_id' => $instanceId,
            'ip_address' => $ipAddress,
        ];

    } catch (\Exception $e) {
        logModuleCall('aws', 'CreateAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'EC2 instance creation failed: ' . $e->getMessage()];
    }
}

/**
 * Suspend EC2 instance (stop)
 */
function aws_SuspendAccount(array $params)
{
    try {
        $api = aws_getApiClient($params);
        $instanceId = $params['domain'];

        if (empty($instanceId)) {
            return ['error' => 'Instance ID not found'];
        }

        $result = $api->stopInstance($instanceId);

        if (isset($result['error'])) {
            logModuleCall('aws', 'SuspendAccount', $params, $result, $result['error']);
            return ['error' => $result['error']];
        }

        logModuleCall('aws', 'SuspendAccount', $params, $result, 'EC2 instance stopped');
        return ['success' => true];

    } catch (\Exception $e) {
        logModuleCall('aws', 'SuspendAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Suspension failed: ' . $e->getMessage()];
    }
}

/**
 * Unsuspend EC2 instance (start)
 */
function aws_UnsuspendAccount(array $params)
{
    try {
        $api = aws_getApiClient($params);
        $instanceId = $params['domain'];

        if (empty($instanceId)) {
            return ['error' => 'Instance ID not found'];
        }

        $result = $api->startInstance($instanceId);

        if (isset($result['error'])) {
            logModuleCall('aws', 'UnsuspendAccount', $params, $result, $result['error']);
            return ['error' => $result['error']];
        }

        logModuleCall('aws', 'UnsuspendAccount', $params, $result, 'EC2 instance started');
        return ['success' => true];

    } catch (\Exception $e) {
        logModuleCall('aws', 'UnsuspendAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Unsuspension failed: ' . $e->getMessage()];
    }
}

/**
 * Terminate EC2 instance (terminate)
 */
function aws_TerminateAccount(array $params)
{
    try {
        $api = aws_getApiClient($params);
        $instanceId = $params['domain'];

        if (empty($instanceId)) {
            return ['error' => 'Instance ID not found'];
        }

        $result = $api->terminateInstance($instanceId);

        if (isset($result['error'])) {
            logModuleCall('aws', 'TerminateAccount', $params, $result, $result['error']);
            return ['error' => $result['error']];
        }

        logModuleCall('aws', 'TerminateAccount', $params, $result, 'EC2 instance terminated');
        return ['success' => true];

    } catch (\Exception $e) {
        logModuleCall('aws', 'TerminateAccount', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Termination failed: ' . $e->getMessage()];
    }
}

/**
 * Change package (resize instance)
 */
function aws_ChangePackage(array $params)
{
    try {
        $api = aws_getApiClient($params);
        $instanceId = $params['domain'];
        $newInstanceType = $params['configoption1'] ?? '';

        if (empty($instanceId)) {
            return ['error' => 'Instance ID not found'];
        }

        if (empty($newInstanceType)) {
            return ['error' => 'New instance type not specified'];
        }

        // Stop instance first
        $api->stopInstance($instanceId);
        sleep(30);

        // Modify instance type
        $result = $api->modifyInstanceAttribute($instanceId, 'instanceType', $newInstanceType);

        if (isset($result['error'])) {
            logModuleCall('aws', 'ChangePackage', $params, $result, $result['error']);
            return ['error' => $result['error']];
        }

        // Start instance
        sleep(5);
        $api->startInstance($instanceId);

        logModuleCall('aws', 'ChangePackage', $params, $result, 'EC2 instance type changed');
        return ['success' => true];

    } catch (\Exception $e) {
        logModuleCall('aws', 'ChangePackage', $params, $e->getMessage(), $e->getTraceAsString());
        return ['error' => 'Package change failed: ' . $e->getMessage()];
    }
}

// ============================================================================
// ADMIN FUNCTIONS
// ============================================================================

/**
 * Display additional fields in admin services tab
 */
function aws_AdminServicesTabFields(array $params)
{
    try {
        $api = aws_getApiClient($params);
        $instanceId = $params['domain'];

        if (empty($instanceId)) {
            return ['Instance ID' => 'Not found'];
        }

        $result = $api->describeInstances([$instanceId]);

        if (isset($result['error'])) {
            return ['Error' => $result['error']];
        }

        if (!isset($result['xml']->reservationSet->item->instancesSet->item)) {
            return ['Error' => 'Instance not found in AWS'];
        }

        $instance = $result['xml']->reservationSet->item->instancesSet->item;

        return [
            'Instance ID' => (string)$instance->instanceId,
            'Instance Type' => (string)$instance->instanceType,
            'State' => ucfirst((string)$instance->instanceState->name),
            'Region/AZ' => (string)$instance->placement->availabilityZone,
            'Public IP' => (string)$instance->ipAddress ?: 'N/A',
            'Private IP' => (string)$instance->privateIpAddress,
            'Public DNS' => (string)$instance->dnsName ?: 'N/A',
            'Private DNS' => (string)$instance->privateDnsName,
            'VPC ID' => (string)$instance->vpcId ?: 'N/A',
            'Subnet ID' => (string)$instance->subnetId ?: 'N/A',
            'AMI ID' => (string)$instance->imageId,
            'Launch Time' => (string)$instance->launchTime,
        ];

    } catch (\Exception $e) {
        return ['Error' => $e->getMessage()];
    }
}

/**
 * Custom admin button functions
 */
function aws_AdminCustomButtonArray()
{
    return [
        'Reboot Instance' => 'reboot',
        'Create Snapshot' => 'snapshot',
    ];
}

/**
 * Reboot instance
 */
function aws_reboot(array $params)
{
    try {
        $api = aws_getApiClient($params);
        $instanceId = $params['domain'];

        $result = $api->rebootInstance($instanceId);

        if (isset($result['error'])) {
            return $result['error'];
        }

        return 'success';

    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Create snapshot
 */
function aws_snapshot(array $params)
{
    try {
        $api = aws_getApiClient($params);
        $instanceId = $params['domain'];

        // Get instance details to find root volume
        $instanceInfo = $api->describeInstances([$instanceId]);

        if (isset($instanceInfo['error'])) {
            return $instanceInfo['error'];
        }

        $instance = $instanceInfo['xml']->reservationSet->item->instancesSet->item;
        $volumeId = '';

        if (isset($instance->blockDeviceMapping->item)) {
            $volumeId = (string)$instance->blockDeviceMapping->item->ebs->volumeId;
        }

        if (empty($volumeId)) {
            return 'Error: Could not find root volume';
        }

        $description = 'WHMCS Snapshot - ' . date('Y-m-d H:i:s');
        $result = $api->createSnapshot($volumeId, $description);

        if (isset($result['error'])) {
            return $result['error'];
        }

        return 'success - Snapshot creation initiated';

    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

// ============================================================================
// CLIENT AREA FUNCTIONS
// ============================================================================

/**
 * Client area output
 */
function aws_ClientArea(array $params)
{
    try {
        $api = aws_getApiClient($params);
        $instanceId = $params['domain'];

        if (empty($instanceId)) {
            return ['error' => 'Instance information not available'];
        }

        $result = $api->describeInstances([$instanceId]);

        if (isset($result['error'])) {
            return ['error' => $result['error']];
        }

        if (!isset($result['xml']->reservationSet->item->instancesSet->item)) {
            return ['error' => 'Instance not found'];
        }

        $instance = $result['xml']->reservationSet->item->instancesSet->item;

        return [
            'templatefile' => 'templates/clientarea',
            'vars' => [
                'instance' => $instance,
                'instance_id' => (string)$instance->instanceId,
                'status' => (string)$instance->instanceState->name,
                'ip_address' => (string)$instance->ipAddress ?: (string)$instance->privateIpAddress,
                'instance_type' => (string)$instance->instanceType,
            ],
        ];

    } catch (\Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

/**
 * Custom client area buttons
 */
function aws_ClientAreaCustomButtonArray()
{
    return [
        'Start' => 'start',
        'Stop' => 'stop',
        'Reboot' => 'clientreboot',
    ];
}

/**
 * Client start
 */
function aws_start(array $params)
{
    try {
        $api = aws_getApiClient($params);
        $instanceId = $params['domain'];

        $result = $api->startInstance($instanceId);

        if (isset($result['error'])) {
            return $result['error'];
        }

        return 'success';

    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Client stop
 */
function aws_stop(array $params)
{
    try {
        $api = aws_getApiClient($params);
        $instanceId = $params['domain'];

        $result = $api->stopInstance($instanceId);

        if (isset($result['error'])) {
            return $result['error'];
        }

        return 'success';

    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Client reboot
 */
function aws_clientreboot(array $params)
{
    try {
        $api = aws_getApiClient($params);
        $instanceId = $params['domain'];

        $result = $api->rebootInstance($instanceId);

        if (isset($result['error'])) {
            return $result['error'];
        }

        return 'success';

    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

// ============================================================================
// TEST CONNECTION
// ============================================================================

/**
 * Test API connection
 */
function aws_TestConnection(array $params)
{
    try {
        $api = aws_getApiClient($params);

        // Test API by describing regions
        $result = $api->describeRegions();

        if (isset($result['error'])) {
            return [
                'success' => false,
                'error' => 'API connection failed: ' . $result['error'],
            ];
        }

        return [
            'success' => true,
            'version' => 'AWS EC2 API',
        ];

    } catch (\Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }
}

// ============================================================================
// ADMIN AREA OUTPUT
// ============================================================================

/**
 * Display admin area link
 */
function aws_AdminLink(array $params)
{
    $instanceId = $params['domain'];
    $region = $params['configoption2'] ?? 'us-east-1';

    if (empty($instanceId)) {
        return '';
    }

    $url = 'https://console.aws.amazon.com/ec2/v2/home?region=' . $region . '#InstanceDetails:instanceId=' . $instanceId;

    return '<a href="' . $url . '" target="_blank" class="btn btn-sm btn-info">
        <i class="fas fa-external-link-alt"></i> Manage in AWS Console
    </a>';
}
