<?php

namespace App\Http\Controllers\Platform_learnova;

use App\Http\Controllers\Controller;
use App\Http\Requests\CertificateRequest\CheckCertificateRequest;
use App\Helpers\ApiResource;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use App\Services\CertificatePdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CertificateController extends Controller
{
    private CertificatePdfService $pdfService;

    public function __construct(CertificatePdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }


    public function verifyBySerial($serialNumber)
    {
        // البحث عن الشهادة باستخدام الرقم التسلسلي فقط
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
    // public function exists(CheckCertificateRequest $request)
    // {
    //     $validated = $request->validated();
    //     $certificate = Certificate::where('student_id', $validated['student_id'])
    //         ->where('course_id', $validated['course_id'])
    //         ->first();

    //     if (!$certificate) {
    //         return ApiResource::sendResponse('Certificate not found for this student in this course.', ['exists' => false], 404);
    //     }

    //     return ApiResource::sendResponse('Certificate exists.', [
    //         'exists' => true,
    //         'certificate' => [
    //             'id' => $certificate->id,
    //             'student_id' => $certificate->student_id,
    //             'course_id' => $certificate->course_id,
    //             'issued_at' => $certificate->issued_at,
    //         ]
    //     ], 200);
    // }

    // public function getStudentCertificates()
    // {
    //     $student = Auth::user(); // جلب المستخدم مباشرة

    //     if (!$student) {
    //         return ApiResource::sendResponse('Student not found.', null, 404);
    //     }

    //     $certificates = Certificate::where('student_id', $student->id)
    //         ->with(['course'])
    //         ->get();

    //     return ApiResource::sendResponse('Certificates retrieved successfully.', [
    //         'student_name' => $student->name,
    //         'certificates_count' => $certificates->count(),
    //         'certificates' => $certificates->map(fn($cert) => [
    //             'id' => $cert->id,
    //             'course_title' => $cert->course->title ?? 'Unknown',
    //             'issued_at' => $cert->issued_at,
    //             // هنا قمنا بإضافة الرابط
    //             'download_url' => $cert->certificate_url ? $this->pdfService->getCertificateDownloadUrl($cert->certificate_url) : null,
    //         ]),
    //     ], 200);
    // }
    //    public function getCourseCertificates($courseId)
    // {
    //     $course = Course::find($courseId);
    //     if (!$course) return ApiResource::sendResponse('Course not found.', null, 404);

    //     $certificates = Certificate::where('course_id', $courseId)->with(['student'])->get();

    //     return ApiResource::sendResponse('Certificates retrieved successfully.', [
    //         'course_title' => $course->title,
    //         'certificates' => $certificates->map(fn($cert) => [
    //             'id' => $cert->id,
    //             'student_name' => $cert->student->name ?? 'Unknown',
    //             'issued_at' => $cert->issued_at,
    //         ]),
    //     ], 200);
    // }

    // public function delete($certificateId)
    // {
    //     $certificate = Certificate::find($certificateId);
    //     if (!$certificate) return ApiResource::sendResponse('Certificate not found.', null, 404);

    //     if ($certificate->certificate_url) {
    //         try {
    //             $this->pdfService->deleteCertificatePdf($certificate->certificate_url);
    //         } catch (\Exception $e) {
    //             return ApiResource::sendResponse('Failed to delete associated PDF file.', null, 500);
    //         }
    //     }

    //     $certificate->delete();
    //     return ApiResource::sendResponse('Certificate deleted successfully.', null, 200);
    // }

    public function downloadPdf($certificateId)
    {
        $certificate = Certificate::find($certificateId);
        if (!$certificate || !$certificate->certificate_url) {
            return ApiResource::sendResponse('PDF file not available.', null, 404);
        }

        if(Auth::id() !== $certificate->student_id && !Auth::user()->hasRole('super_admin')) {
            return ApiResource::sendResponse('Unauthorized access to this certificate.', null, 403);
        }

        $pdfUrl = $this->pdfService->getCertificateDownloadUrl($certificate->certificate_url);
        return ApiResource::sendResponse('Download URL retrieved successfully.', ['download_url' => $pdfUrl], 200);
    }

    public function show($certificateId)
    {
        
        $certificate = Certificate::with(['student', 'course'])->find($certificateId);
        
        if (!$certificate) return ApiResource::sendResponse('Certificate not found.', null, 200);

        if(Auth::id() !== $certificate->student_id && !Auth::user()->hasRole('super_admin')) {
            return ApiResource::sendResponse('Unauthorized access to this certificate.', null, 403);
        }
        return ApiResource::sendResponse('Certificate information retrieved successfully.', [
            'id' => $certificate->id,
            'student_name' => $certificate->student->name,
            'course_title' => $certificate->course->title,
            'issued_at' => $certificate->issued_at,
            'serial_number' => $certificate->serial_number,
            'pdf_url' => $certificate->certificate_url ? $this->pdfService->getCertificateDownloadUrl($certificate->certificate_url) : null,
        ], 200);
    }

    
    }
