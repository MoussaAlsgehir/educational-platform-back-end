<?php

namespace App\Http\Controllers\Platform_learnova;

use App\Http\Controllers\Controller;
use App\Helpers\ApiResource;
use App\Models\Certificate;
use App\Services\CertificatePdfService;
use Illuminate\Support\Facades\Auth;

class CertificateController extends Controller
{
    private CertificatePdfService $pdfService;

    public function __construct(CertificatePdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Public verification endpoint by serial number.
     *
     * @param string $serialNumber
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyBySerial($serialNumber)
    {
        $certificate = Certificate::with(['student', 'course'])
            ->where('serial_number', $serialNumber)
            ->first();

        if (!$certificate) {
            return ApiResource::sendResponse('Certificate not found.', null, 404);
        }

        return ApiResource::sendResponse('Certificate verified successfully.', [
            'student_name' => $certificate->student->name,
            'course_title' => $certificate->course->title,
            'issued_at'    => $certificate->issued_at,
            'status'       => 'Valid'
        ], 200);
    }

    /**
     * Get all certificates for the currently authenticated student.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStudentCertificates()
    {
        $student = Auth::user();

        if (!$student) {
            return ApiResource::sendResponse('Student not found.', null, 404);
        }

        $certificates = Certificate::where('student_id', $student->id)
            ->with(['course'])
            ->get();

        return ApiResource::sendResponse('Certificates retrieved successfully.', [
            'student_name'       => $student->name,
            'certificates_count' => $certificates->count(),
            'certificates'       => $certificates->map(function ($cert) {
                return [
                    'id'           => $cert->id,
                    'course_title' => $cert->course->title,
                    'issued_at'    => $cert->issued_at,
                    'download_url' => $this->pdfService->getCertificateDownloadUrl($cert->certificate_url),
                ];
            }),
        ], 200);
    }

    /**
     * Get PDF download URL for a specific certificate.
     *
     * @param int $certificateId
     * @return \Illuminate\Http\JsonResponse
     */
    public function downloadPdf($certificateId)
    {
        $certificate = Certificate::find($certificateId);

        if (!$certificate) {
            return ApiResource::sendResponse('Certificate not found.', null, 404);
        }

        // Authorization check
        if (Auth::id() !== $certificate->student_id) {
            return ApiResource::sendResponse('Unauthorized access to this certificate.', null, 403);
        }

        $pdfUrl = $this->pdfService->getCertificateDownloadUrl($certificate->certificate_url);

        return ApiResource::sendResponse('Download URL retrieved successfully.', [
            'download_url' => $pdfUrl
        ], 200);
    }

    /**
     * Show certificate information.
     *
     * @param int $certificateId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($certificateId)
    {
        $certificate = Certificate::with(['student', 'course'])->find($certificateId);

        if (!$certificate) {
            return ApiResource::sendResponse('Certificate not found.', null, 404);
        }

        // Authorization check
        if (Auth::id() !== $certificate->student_id) {
            return ApiResource::sendResponse('Unauthorized access to this certificate.', null, 403);
        }

        return ApiResource::sendResponse('Certificate information retrieved successfully.', [
            'id'            => $certificate->id,
            'student_name'  => $certificate->student->name,
            'course_title'  => $certificate->course->title,
            'issued_at'     => $certificate->issued_at,
            'serial_number' => $certificate->serial_number,
            'pdf_url'       => $this->pdfService->getCertificateDownloadUrl($certificate->certificate_url),
        ], 200);
    }
}
