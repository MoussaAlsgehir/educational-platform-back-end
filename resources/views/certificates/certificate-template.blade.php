<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { size: A4 landscape; margin: 0; }
        body { font-family: 'Helvetica', sans-serif; margin: 0; padding: 0; background-color: #fdfbf7; }
        
        /* جعل الحاوية تأخذ كامل أبعاد الورقة لملء الفراغ بشكل متناسق */
        .page-wrapper {
            width: 842px; height: 595px;
            margin: 0 auto;
            padding-top: 80px; /* دفع المحتوى لأسفل قليلاً ليكون في المركز */
            text-align: center;
        }

        /* تكبير الخطوط قليلاً لتملأ الفراغ */
        .brand { font-size: 36px; font-weight: bold; color: #0b1325; margin-bottom: 5px; }
        .slogan { font-size: 13px; color: #c59b27; text-transform: uppercase; letter-spacing: 5px; }
        
        .cert-title { font-size: 55px; font-weight: bold; color: #0b1325; letter-spacing: 10px; margin-top: 40px; }
        .cert-subtitle { font-size: 18px; color: #c59b27; letter-spacing: 6px; margin-top: 5px; }
        .divider { width: 300px; height: 2px; background-color: #c59b27; margin: 30px auto; }
        
        .presented-to { font-size: 18px; color: #666; font-style: italic; margin-top: 20px; }
        .student-name { font-size: 55px; color: #0b1325; font-style: italic; font-weight: bold; margin: 25px 0; }
        
        .course-label { font-size: 18px; color: #666; margin-top: 15px; }
        .course-name { font-size: 38px; color: #c59b27; font-weight: bold; margin-top: 10px; }

        /* دفع الـ QR للأسفل قليلاً لملء مساحة الورقة */
        .qr-section { margin-top: 70px; }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <div class="brand">Learnova</div>
        
        <div class="cert-title">CERTIFICATE</div>
        <div class="cert-subtitle">OF COMPLETION</div>
        <div class="divider"></div>

        <div class="presented-to">This certificate is proudly presented to</div>
        <div class="student-name">{{ $student_name }}</div>
        
        <div class="course-label">For successfully completing the course</div>
        <div class="course-name">{{ $course_title }}</div>

        <div class="qr-section">
            <table style="margin: 0 auto;">
                <tr>
                    <td style="padding-right: 20px;">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode(url('/api/verify/' . $serial_number)) }}" 
                             style="width: 85px; height: 85px; border: 1px solid #ddd;">
                    </td>
                    <td style="text-align: left; font-size: 12px; color: #555; line-height: 1.5;">
                        <strong>Serial Number:</strong><br>{{ $serial_number }}<br>
                        <strong>Issue Date:</strong><br>{{ $issued_date }}
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>