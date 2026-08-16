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




    /**
     * Stream the certificate PDF as a PNG image for frontend preview.
     *
     * @param int $certificateId
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    // public function previewAsImage($certificateId)
    // {
    //     $certificate = Certificate::find($certificateId);

    //     if (!$certificate) {
    //         return ApiResource::sendResponse('Certificate not found.', null, 404);
    //     }

    //     // التحقق من الصلاحيات (نفس المنطق المستخدم في دالة التحميل)
    //     if (Auth::id() !== $certificate->student_id) {
    //         return ApiResource::sendResponse('Unauthorized access to this certificate.', null, 403);
    //     }

    //     // استخراج المسار الفعلي لملف الـ PDF على السيرفر
    //     // ملاحظة: تأكد من تعديل هذا السطر ليتوافق مع طريقة حفظك للملف (مثلاً إذا كان داخل storage/app/public)
    //     $pdfPath = storage_path('app/public/' . $certificate->certificate_url);

    //     if (!file_exists($pdfPath)) {
    //         return ApiResource::sendResponse('Certificate file not found on server.', null, 404);
    //     }

    //     try {
    //         // استخدام مكتبة Spatie لتحويل الـ PDF إلى صورة في الذاكرة بدون حفظ على القرص
    //         $pdf = new \Spatie\PdfToImage\Pdf($pdfPath);

    //         // اختر الصفحة الأولى وحصل على بيانات الصورة كـ binary PNG
    //         $imageData = $pdf->setOutputFormat('png')->selectPage(1)->getImageData();

    //         // إرجاع الصورة مباشرة للفرونت إند مع تفعيل الكاش لتسريع الفتح في المرات القادمة
    //         return response($imageData, 200, [
    //             'Content-Type' => 'image/png',
    //             'Cache-Control' => 'max-age=2592000, public', // كاش لمدة شهر
    //         ]);
    //     } catch (\Spatie\PdfToImage\Exceptions\PdfException $e) {
    //         return ApiResource::sendResponse('PDF conversion error: ' . $e->getMessage(), null, 500);
    //     } catch (\Exception $e) {
    //         return ApiResource::sendResponse('Error generating image preview: ' . $e->getMessage(), null, 500);
    //     }
    // }
}
