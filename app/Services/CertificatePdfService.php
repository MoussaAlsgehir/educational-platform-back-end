<?php

namespace App\Services;

use App\Models\Certificate;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class CertificatePdfService
{
    /**
     * Generate PDF certificate and return stored file path and serial number.
     *
     * @param Certificate $certificate
     * @return array Array containing 'certificate_url' and 'serial_number'
     */
    public function generateCertificatePdf(Certificate $certificate): array
    {
        // 1. Generate serial number BEFORE trying to output PDF,
        // because $certificate->id is null when called before saving to DB.
        $serialNumber = $this->generateSerialNumber();

        $data = [
            'student_name'  => $certificate->student->name ?? 'Student Name',
            'course_title'  => $certificate->course->title ?? 'Course Title',
            'issued_date'   => $certificate->issued_at ? $certificate->issued_at->format('Y-m-d') : now()->format('Y-m-d'),
            'serial_number' => $serialNumber,
        ];

        $pdf = Pdf::loadView('certificates.certificate-template', $data);
        $pdf->setPaper('A4', 'landscape');

        $pdf->setOptions([
            'isRemoteEnabled'      => true,
            'isHtml5ParserEnabled' => true,
            'isPhpEnabled'         => true,
            'chroot'               => public_path(),
        ]);

        $filename = "certificates/cert_" . $serialNumber . ".pdf";

        Storage::disk('public')->put($filename, $pdf->output());

        return [
            'certificate_url' => $filename,
            'serial_number'   => $serialNumber,
        ];
    }

    /**
     * Generate a unique serial number for the certificate.
     *
     * @return string
     */
    private function generateSerialNumber(): string
    {
        return strtoupper(substr(md5(uniqid(microtime(), true)), 0, 8));
    }

    /**
     * Delete certificate PDF file from storage.
     *
     * @param string $path
     * @return bool
     */
    public function deleteCertificatePdf(string $path): bool
    {
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }

        return false;
    }

    /**
     * Get the full public URL for downloading/viewing the certificate PDF.
     *
     * @param string $path
     * @return string
     */
    public function getCertificateDownloadUrl(string $path): string
    {
        // Generates full HTTP URL using APP_URL and symlink (e.g. http://domain.com/storage/certificates/cert_ABC123.pdf)
        return asset('storage/' . $path);
    }
}
