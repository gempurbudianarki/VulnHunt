<?php

namespace Database\Seeders;

use App\Models\VulnerabilityType;
use Illuminate\Database\Seeder;

class CweCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cwes = [
            ['cwe_id' => 'CWE-79', 'name' => 'Cross-Site Scripting (XSS)', 'category' => 'Injection'],
            ['cwe_id' => 'CWE-89', 'name' => 'SQL Injection', 'category' => 'Injection'],
            ['cwe_id' => 'CWE-352', 'name' => 'Cross-Site Request Forgery (CSRF)', 'category' => 'Session Management'],
            ['cwe_id' => 'CWE-639', 'name' => 'Authorization Bypass Through User-Controlled Key (IDOR)', 'category' => 'Access Control'],
            ['cwe_id' => 'CWE-284', 'name' => 'Improper Access Control', 'category' => 'Access Control'],
            ['cwe_id' => 'CWE-285', 'name' => 'Improper Authorization', 'category' => 'Access Control'],
            ['cwe_id' => 'CWE-287', 'name' => 'Improper Authentication', 'category' => 'Authentication'],
            ['cwe_id' => 'CWE-798', 'name' => 'Hard-coded Credentials', 'category' => 'Authentication'],
            ['cwe_id' => 'CWE-200', 'name' => 'Exposure of Sensitive Information', 'category' => 'Information Disclosure'],
            ['cwe_id' => 'CWE-538', 'name' => 'Insertion of Sensitive Information into Externally-Accessible File', 'category' => 'Information Disclosure'],
            ['cwe_id' => 'CWE-548', 'name' => 'Exposure of Information Through Directory Listing', 'category' => 'Information Disclosure'],
            ['cwe_id' => 'CWE-22', 'name' => 'Path Traversal', 'category' => 'File Handling'],
            ['cwe_id' => 'CWE-434', 'name' => 'Unrestricted File Upload', 'category' => 'File Handling'],
            ['cwe_id' => 'CWE-918', 'name' => 'Server-Side Request Forgery (SSRF)', 'category' => 'Server Security'],
            ['cwe_id' => 'CWE-601', 'name' => 'Open Redirect', 'category' => 'Web Security'],
            ['cwe_id' => 'CWE-611', 'name' => 'XML External Entity (XXE)', 'category' => 'Data Processing'],
            ['cwe_id' => 'CWE-78', 'name' => 'OS Command Injection', 'category' => 'Injection'],
            ['cwe_id' => 'CWE-94', 'name' => 'Code Injection', 'category' => 'Injection'],
            ['cwe_id' => 'CWE-502', 'name' => 'Deserialization of Untrusted Data', 'category' => 'Data Processing'],
            ['cwe_id' => 'CWE-1021', 'name' => 'Improper Restriction of Rendered UI Layers (Clickjacking)', 'category' => 'Web Security'],
            ['cwe_id' => 'CWE-693', 'name' => 'Protection Mechanism Failure (Header Keamanan Hilang)', 'category' => 'Configuration'],
            ['cwe_id' => 'CWE-942', 'name' => 'Permissive Cross-domain Policy (CORS Misconfiguration)', 'category' => 'Configuration'],
            ['cwe_id' => 'CWE-307', 'name' => 'Improper Restriction of Excessive Authentication Attempts', 'category' => 'Authentication'],
            ['cwe_id' => 'CWE-521', 'name' => 'Weak Password Requirements', 'category' => 'Authentication'],
            ['cwe_id' => 'CWE-319', 'name' => 'Cleartext Transmission of Sensitive Information', 'category' => 'Cryptography'],
            ['cwe_id' => 'CWE-327', 'name' => 'Use of a Broken or Risky Cryptographic Algorithm', 'category' => 'Cryptography'],
            ['cwe_id' => 'CWE-16', 'name' => 'Configuration', 'category' => 'Configuration'],
            ['cwe_id' => 'CWE-OTHER', 'name' => 'Lainnya (Perlu Klasifikasi Panitia)', 'category' => 'Other'],
        ];

        foreach ($cwes as $cwe) {
            VulnerabilityType::updateOrCreate(
                ['cwe_id' => $cwe['cwe_id']],
                [
                    'name' => $cwe['name'],
                    'category' => $cwe['category'],
                    'is_active' => true,
                ]
            );
        }
    }
}
