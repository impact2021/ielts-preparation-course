<?php
/**
 * PDF Generator for Receipts
 * Uses TCPDF library
 */

if (!defined('ABSPATH')) {
    exit;
}

class IELTS_CM_PDF_Generator {
    
    /**
     * Generate receipt PDF
     */
    public function generate_receipt_pdf($membership, $payment, $user) {
        // Load FPDF library
        if (!class_exists('FPDF')) {
            require_once IELTS_CM_PLUGIN_DIR . 'lib/fpdf/fpdf.php';
        }
        
        // Get company details
        $company_name = get_option('ielts_cm_company_name', 'IELTS Preparation Course');
        $company_address = get_option('ielts_cm_company_address', '');
        $company_gst = get_option('ielts_cm_company_gst', '');
        $company_phone = get_option('ielts_cm_company_phone', '');
        $company_email = get_option('ielts_cm_company_email', '');
        $company_website = get_option('ielts_cm_company_website', '');
        $company_logo = get_option('ielts_cm_company_logo', '');
        
        // Create new PDF document
        $pdf = new FPDF('P', 'mm', 'A4');
        
        // Set margins and add a page
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->AddPage();
        
        // Add company logo if available
        if ($company_logo) {
            $logo_path = $this->get_logo_path($company_logo);
            if ($logo_path && file_exists($logo_path)) {
                $pdf->Image($logo_path, 15, 15, 50);
                $pdf->Ln(30);
            }
        }
        
        // Company details
        $pdf->SetFont('Helvetica', 'B', 14);
        $pdf->Cell(0, 8, $company_name, 0, 1, 'L');
        
        $pdf->SetFont('Helvetica', '', 9);
        if ($company_address) {
            $pdf->MultiCell(0, 4, $company_address, 0, 'L');
        }
        if ($company_phone) {
            $pdf->Cell(0, 4, 'Phone: ' . $company_phone, 0, 1, 'L');
        }
        if ($company_email) {
            $pdf->Cell(0, 4, 'Email: ' . $company_email, 0, 1, 'L');
        }
        if ($company_website) {
            $pdf->Cell(0, 4, 'Website: ' . $company_website, 0, 1, 'L');
        }
        if ($company_gst) {
            $pdf->Cell(0, 4, 'GST/Tax Number: ' . $company_gst, 0, 1, 'L');
        }
        
        $pdf->Ln(10);
        
        // Receipt title
        $pdf->SetFont('Helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'PAYMENT RECEIPT', 0, 1, 'C');
        
        $pdf->Ln(5);
        
        // Receipt details header
        $receipt_number = 'REC-' . str_pad($membership->id, 6, '0', STR_PAD_LEFT);
        $receipt_date = $payment ? date('F j, Y', strtotime($payment->payment_date)) : date('F j, Y');
        
        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->Cell(95, 6, 'Receipt Number:', 0, 0, 'L');
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->Cell(0, 6, $receipt_number, 0, 1, 'L');
        
        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->Cell(95, 6, 'Receipt Date:', 0, 0, 'L');
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->Cell(0, 6, $receipt_date, 0, 1, 'L');
        
        $pdf->Ln(8);
        
        // Customer details
        $pdf->SetFillColor(240, 240, 240);
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->Cell(0, 8, 'CUSTOMER DETAILS', 0, 1, 'L', true);
        
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->Cell(0, 6, 'Name: ' . ($user->display_name ?: $user->user_login), 0, 1, 'L');
        $pdf->Cell(0, 6, 'Email: ' . $user->user_email, 0, 1, 'L');
        $pdf->Cell(0, 6, 'User ID: ' . $user->ID, 0, 1, 'L');
        
        $pdf->Ln(8);
        
        // Payment details table
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->Cell(0, 8, 'PAYMENT DETAILS', 0, 1, 'L', true);
        
        $pdf->Ln(2);
        
        // Table header
        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->Cell(95, 7, 'Description', 1, 0, 'L', true);
        $pdf->Cell(40, 7, 'Amount', 1, 0, 'R', true);
        $pdf->Cell(45, 7, 'Currency', 1, 1, 'C', true);
        
        // Table content
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetFillColor(255, 255, 255);
        
        $description = 'IELTS Membership';
        $amount = $payment ? number_format($payment->amount, 2) : '0.00';
        $currency = $payment ? $payment->currency : 'USD';
        
        $pdf->Cell(95, 7, $description, 1, 0, 'L');
        $pdf->Cell(40, 7, $amount, 1, 0, 'R');
        $pdf->Cell(45, 7, $currency, 1, 1, 'C');
        
        $pdf->Ln(8);
        
        // Membership period
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->SetFillColor(240, 240, 240);
        $pdf->Cell(0, 8, 'MEMBERSHIP PERIOD', 0, 1, 'L', true);
        
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->Cell(95, 6, 'Start Date:', 0, 0, 'L');
        $pdf->Cell(0, 6, date('F j, Y', strtotime($membership->start_date)), 0, 1, 'L');
        
        $pdf->Cell(95, 6, 'Expiry Date:', 0, 0, 'L');
        $pdf->Cell(0, 6, date('F j, Y', strtotime($membership->end_date)), 0, 1, 'L');
        
        // Calculate days
        $start = new DateTime($membership->start_date);
        $end = new DateTime($membership->end_date);
        $days = $start->diff($end)->days;
        
        $pdf->Cell(95, 6, 'Duration:', 0, 0, 'L');
        $pdf->Cell(0, 6, $days . ' days', 0, 1, 'L');
        
        $pdf->Ln(8);
        
        // Payment information
        if ($payment) {
            $pdf->SetFont('Helvetica', 'B', 11);
            $pdf->SetFillColor(240, 240, 240);
            $pdf->Cell(0, 8, 'TRANSACTION INFORMATION', 0, 1, 'L', true);
            
            $pdf->SetFont('Helvetica', '', 10);
            
            if ($payment->payment_method) {
                $pdf->Cell(95, 6, 'Payment Method:', 0, 0, 'L');
                $pdf->Cell(0, 6, ucfirst($payment->payment_method), 0, 1, 'L');
            }
            
            if ($payment->transaction_id) {
                $pdf->Cell(95, 6, 'Transaction ID:', 0, 0, 'L');
                $pdf->Cell(0, 6, $payment->transaction_id, 0, 1, 'L');
            }
            
            $pdf->Cell(95, 6, 'Payment Date:', 0, 0, 'L');
            $pdf->Cell(0, 6, date('F j, Y H:i:s', strtotime($payment->payment_date)), 0, 1, 'L');
            
            $pdf->Cell(95, 6, 'Status:', 0, 0, 'L');
            $pdf->Cell(0, 6, ucfirst($payment->status), 0, 1, 'L');
        }
        
        $pdf->Ln(15);
        
        // Footer note
        $pdf->SetFont('Helvetica', 'I', 8);
        $pdf->MultiCell(0, 4, 'This is a computer-generated receipt and does not require a signature. Please keep this receipt for your records.', 0, 'C');
        
        // Generate temporary file
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/ielts-receipts';
        
        if (!file_exists($temp_dir)) {
            wp_mkdir_p($temp_dir);
            // Protect directory with .htaccess
            $htaccess = $temp_dir . '/.htaccess';
            file_put_contents($htaccess, 'Deny from all');
        }
        
        $filename = 'receipt-' . $membership->id . '-' . time() . '.pdf';
        $filepath = $temp_dir . '/' . $filename;
        
        // Output PDF to file
        $pdf->Output('F', $filepath);
        
        return $filepath;
    }
    
    /**
     * Get local path for logo
     */
    private function get_logo_path($logo_url) {
        $upload_dir = wp_upload_dir();
        $base_url = $upload_dir['baseurl'];
        $base_dir = $upload_dir['basedir'];
        
        if (strpos($logo_url, $base_url) === 0) {
            $relative_path = str_replace($base_url, '', $logo_url);
            return $base_dir . $relative_path;
        }
        
        return false;
    }
}
