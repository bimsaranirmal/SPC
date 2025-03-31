<?php
// Include the FPDF library
require('fpdf186/fpdf.php');

// Define a class that extends FPDF to create SPC system policy documents
class SPCPolicyPDF extends FPDF {
    // Page header
    function Header() {
        //SPC Logo - replace 'spc_logo.png' with your actual logo path
        $this->Image('images/OIP-removebg-preview.png', 10, 6, 30);
        
        // Arial bold 15
        $this->SetFont('Arial', 'B', 15);
        
        // Title
        $this->Cell(0, 10, $this->documentTitle, 0, 1, 'C');
        
        // Subtitle - SPC System
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, 'SPC System', 0, 1, 'C');
        
        // Line break
        $this->Ln(5);
        
        // Line separator
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(5);
    }

    // Page footer
    function Footer() {
        // Position at 1.5 cm from bottom
        $this->SetY(-15);
        
        // Arial italic 8
        $this->SetFont('Arial', 'I', 8);
        
        // Page number
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
        
        // Company and system info
        $this->Cell(0, 10, 'SPC System © ' . date('Y'), 0, 0, 'R');
    }
    
    // Set document title
    private $documentTitle;
    function setTitle($title, $isUTF8 = false) {
        $this->documentTitle = $title;
        parent::SetTitle($title, $isUTF8);
    }
    
    // Main content function with section handling
    function addContent($content, $sections = []) {
        $this->SetFont('Arial', '', 11);
        
        if (!empty($sections)) {
            foreach ($sections as $section => $text) {
                // Add section header
                $this->SetFont('Arial', 'B', 12);
                $this->Cell(0, 10, $section, 0, 1);
                $this->Ln(2);
                
                // Add section content
                $this->SetFont('Arial', '', 11);
                $this->MultiCell(0, 6, $text);
                $this->Ln(5);
            }
        } else {
            // If no sections provided, just add the content
            $this->MultiCell(0, 6, $content);
        }
    }
    
    // Function to add date of policy
    function addPolicyDate($date) {
        $this->SetFont('Arial', 'I', 10);
        $this->Cell(0, 10, 'Last Updated: ' . $date, 0, 1, 'R');
        $this->Ln(5);
    }
}

/**
 * Function to generate policy PDF
 * 
 * @param string $policy_type Type of policy ('privacy', 'terms', 'refund')
 * @return void
 */
function generatePolicyPDF($policy_type) {
    // Initialize content variables
    $title = '';
    $filename = '';
    $sections = [];
    $last_updated = date('F d, Y');
    
    // Set policy-specific content
    switch ($policy_type) {
        case 'privacy':
            $title = 'Privacy Policy';
            $filename = 'spc_privacy_policy.pdf';
            $sections = [
                'Information Collection' => 'The SPC System collects personal information necessary for providing our services, including but not limited to your name, contact information, and usage data. This information is collected when you register, use our services, or interact with our system.',
                'Information Usage' => 'We use your information to provide and improve our SPC System services, process transactions, send notifications, and communicate with you about updates or changes to our services.',
                'Information Protection' => 'The SPC System employs industry-standard security measures to protect your personal information from unauthorized access, alteration, or disclosure.',
                'Cookies and Tracking' => 'Our system uses cookies and similar technologies to enhance your experience and collect usage information. You can manage cookie preferences through your browser settings.',
                'Third-Party Disclosure' => 'We do not sell, trade, or otherwise transfer your personal information to outside parties without your consent, except as required for service provision or legal compliance.',
                'Updates to Policy' => 'This Privacy Policy may be updated periodically to reflect changes in our practices. Users will be notified of significant changes.'
            ];
            break;
            
        case 'terms':
            $title = 'Terms & Conditions';
            $filename = 'spc_terms_conditions.pdf';
            $sections = [
                'Acceptance of Terms' => 'By accessing or using the SPC System, you agree to be bound by these Terms and Conditions. If you disagree with any part of these terms, you may not access or use our system.',
                'User Accounts' => 'You are responsible for maintaining the confidentiality of your account credentials and for all activities that occur under your account. Notify us immediately of any unauthorized use.',
                'Intellectual Property' => 'All content, features, and functionality of the SPC System are owned by us and are protected by international copyright, trademark, and other intellectual property laws.',
                'Prohibited Activities' => 'You agree not to misuse the SPC System or help anyone else do so. This includes attempting to gain unauthorized access, introducing malware, or interfering with service operation.',
                'Limitation of Liability' => 'The SPC System and its administrators shall not be liable for any indirect, incidental, special, consequential, or punitive damages resulting from your use or inability to use the system.',
                'Governing Law' => 'These Terms shall be governed by and construed in accordance with the laws applicable in your jurisdiction, without regard to its conflict of law provisions.',
                'Changes to Terms' => 'We reserve the right to modify these terms at any time. We will provide notice of significant changes. Continued use of the SPC System after such modifications constitutes acceptance of the updated terms.'
            ];
            break;
            
        case 'refund':
            $title = 'Refund & Return Policy';
            $filename = 'spc_refund_policy.pdf';
            $sections = [
                'Refund Eligibility' => 'Refunds for the SPC System services may be issued within 30 days of purchase if the service does not function as described or if technical issues prevent proper usage that our support team cannot resolve.',
                'Refund Process' => 'To request a refund, contact our customer support team with your purchase details and reason for the refund request. All refund requests will be processed within 5-10 business days.',
                'Non-Refundable Items' => 'Certain services, such as custom implementations or after full deployment and acceptance, are non-refundable. These will be clearly marked at the time of purchase.',
                'Subscription Cancellations' => 'You may cancel a subscription at any time. For annual subscriptions, a prorated refund may be issued for the unused portion. Monthly subscriptions will terminate at the end of the current billing period.',
                'Service Credits' => 'In some cases, we may offer service credits instead of monetary refunds. These credits can be applied to future purchases or renewals within the SPC System.',
                'Exceptional Circumstances' => 'Refund requests outside the standard policy timeframe may be considered on a case-by-case basis for exceptional circumstances.'
            ];
            break;
            
        default:
            die('Invalid policy type specified');
    }
    
    // Create new PDF instance
    $pdf = new SPCPolicyPDF();
    $pdf->AliasNbPages();
    $pdf->setTitle($title);
    $pdf->AddPage();
    $pdf->addPolicyDate($last_updated);
    $pdf->addContent('', $sections);
    
    // Output the PDF
    $pdf->Output('D', $filename);
    exit;
}

// Check which policy is requested
if (isset($_GET['policy'])) {
    $policy_type = $_GET['policy'];
    generatePolicyPDF($policy_type);
} else {
    // If no policy is specified, display download links
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>SPC System - Legal Documents</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                max-width: 800px;
                margin: 0 auto;
                padding: 20px;
            }
            h1 {
                text-align: center;
                color: #333;
            }
            .policy-links {
                display: flex;
                flex-direction: column;
                gap: 15px;
                margin-top: 30px;
            }
            .policy-link {
                display: block;
                padding: 15px;
                background-color: #f5f5f5;
                border: 1px solid #ddd;
                border-radius: 5px;
                text-decoration: none;
                color: #333;
                font-weight: bold;
                transition: background-color 0.3s;
            }
            .policy-link:hover {
                background-color: #e0e0e0;
            }
        </style>
    </head>
    <body>
        <h1>SPC System - Legal Documents</h1>
        <p>Please select a document to download:</p>
        
        <div class="policy-links">
            <a href="?policy=privacy" class="policy-link">Download Privacy Policy</a>
            <a href="?policy=terms" class="policy-link">Download Terms & Conditions</a>
            <a href="?policy=refund" class="policy-link">Download Refund & Return Policy</a>
        </div>
    </body>
    </html>
    <?php
}
?>