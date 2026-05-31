<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>رمز التحقق (OTP)</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f6f9fc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        .email-wrapper {
            width: 100%;
            background-color: #f6f9fc;
            padding: 40px 10px;
        }
        .email-card {
            max-width: 500px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            border: 1px solid #eef2f5;
        }
        .email-header {
            background-color: #4f46e5; /* لون أزرق احترافي - يمكنك تغييره للون هويتك */
            padding: 30px;
            text-align: center;
        }
        .email-header h2 {
            color: #ffffff;
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .email-body {
            padding: 40px 30px;
            text-align: center;
            color: #334155;
        }
        .email-body p {
            font-size: 16px;
            line-height: 1.6;
            margin: 0 0 25px 0;
        }
        .otp-container {
            background-color: #f1f5f9;
            border: 2px dashed #cbd5e1;
            border-radius: 8px;
            padding: 15px 30px;
            display: inline-block;
            margin-bottom: 25px;
        }
        .otp-code {
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 6px;
            color: #1e293b;
            margin: 0;
        }
        .email-footer {
            background-color: #f8fafc;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        .email-footer p {
            font-size: 13px;
            color: #64748b;
            margin: 0;
        }
        .warning-text {
            color: #ef4444 !important;
            font-size: 14px !important;
            font-weight: 500;
        }
    </style>
</head>
<body>

    <div class="email-wrapper">
        <div class="email-card">

            <!-- الهيدر / الشعار -->
            <div class="email-header">
                <h2>تأكيد الهوية</h2>
            </div>

            <!-- محتوى الرسالة -->
            <div class="email-body">
                <p>مرحباً بك في </p>
                <p><br>Learnova</p>
                <p>لقد تلقينا طلباً لتسجيل الدخول أو إتمام عملية في حسابك. يرجى استخدام رمز التحقق (OTP) التالي لإكمال العملية:</p>

                <!-- طباعة الرمز داخل الصندوق المخصص -->
                <div class="otp-container">
                    <h1 class="otp-code">{{ $otpCode }}</h1>
                </div>

                <p class="warning-text">هذا الرمز صالح لمدة 10 دقائق فقط. يرجى عدم مشاركته مع أي شخص لحماية حسابك.</p>
            </div>

            <!-- الفوتر -->
            <div class="email-footer">
                <p>إذا لم تكن أنت من طلب هذا الرمز، يمكنك تجاهل هذا الإيميل بأمان.</p>
                <p style="margin-top: 8px; font-size: 11px;">&copy; {{ date('Y') }} جميع الحقوق محفوظة.</p>
            </div>

        </div>
    </div>

</body>
</html>
