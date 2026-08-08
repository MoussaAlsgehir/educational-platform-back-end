 <!DOCTYPE html>
 <html>

 <head>
     <meta charset="UTF-8">
     <title>Certificate of Completion</title>
     <style>
         /* إعدادات الصفحة */
         @page {
             size: A4 landscape;
             margin: 0;
         }

         body {
             font-family: 'Helvetica', 'DejaVu Sans', sans-serif;
             margin: 0;
             padding: 0;
             background-color: #FAF9F6;
             color: #152238;
         }

         /* الإطارات الخارجية والداخلية */
         .border-outer {
             position: absolute;
             top: 15px;
             left: 15px;
             right: 15px;
             bottom: 15px;
             border: 3px solid #6C3CF0;
             z-index: -2;
         }

         .border-inner {
             position: absolute;
             top: 25px;
             left: 25px;
             right: 25px;
             bottom: 25px;
             border: 2px solid #7d5bd8;
             z-index: -1;
         }

         /* حاوية المحتوى الأساسية مع ترك مسافة آمنة من الإطارات */
         .content {
             padding-top: 50px;
             text-align: center;
             width: 100%;
         }

         /* الهيدر والعناوين */
         .brand {
             font-size: 32px;
             font-weight: bold;
             color: #152238;
             margin-bottom: 15px;
         }

         .cert-title {
             font-size: 55px;
             font-weight: bold;
             color: #152238;
             letter-spacing: 12px;
             margin: 0;
         }

         .cert-subtitle {
             font-size: 20px;
             color: #6C3CF0;
             letter-spacing: 8px;
             margin-top: 10px;
             text-transform: uppercase;
         }

         .divider {
             width: 350px;
             height: 2px;
             background-color: #7d5bd8;
             margin: 20px auto;
         }

         /* قسم الطالب */
         .presented-to {
             font-size: 18px;
             color: #555;
             font-style: italic;
             margin-top: 10px;
         }

         .student-name {
             font-size: 52px;
             color: #152238;
             font-style: italic;
             font-weight: bold;
             margin: 15px 0;
         }

         /* قسم الكورس */
         .course-label {
             font-size: 18px;
             color: #555;
             margin-bottom: 15px;
         }

         /* البادج مصمم بجدول لضمان توافقه التام مع Dompdf */
         .badge-table {
             margin: 0 auto;
             border: 2px solid #6C3CF0;
             background-color: #EFE8FF;
             border-radius: 25px;
         }

         .badge-table td {
             padding: 12px 35px;
             font-size: 30px;
             font-weight: bold;
             color: #152238;
         }

         /* الفوتر: استخدام جدول صارم التقسيم لمنع أي تداخل (توزيع العرض 25% لكل عمود) */
         .footer-table {
             width: 90%;
             margin: 35px auto 0 auto;
             table-layout: fixed;
             /* يمنع الأعمدة من التمدد وتخريب التصميم */
             border-collapse: collapse;
         }

         .footer-table td {
             vertical-align: bottom;
             /* جعل كل العناصر بمحاذاة سفلية واحدة */
             text-align: center;
         }

         /* التوقيعات */
         .signature-line {
             width: 150px;
             margin: 0 auto;
             border-top: 1px solid #152238;
             padding-top: 8px;
             font-size: 15px;
             font-weight: bold;
             color: #152238;
         }

         /* الكيو آر كود */
         .qr-image {
             width: 75px;
             height: 75px;
             border: 1px solid #ddd;
             margin-bottom: 8px;
         }

         .qr-text {
             font-size: 12px;
             color: #555;
             line-height: 1.5;
         }

         /* الختم: تم إصلاحه وتصميمه بحسابات ثابتة ليتوافق مع Dompdf */
         .seal-outer {
             width: 100px;
             height: 100px;
             margin: 0 auto;
             border: 3px solid #7d5bd8;
             border-radius: 45px;
         }

         .seal-inner {
             width: 88px;
             height: 88px;
             margin: 4px auto 0 auto;
             border: 2px dashed #7d5bd8;
             border-radius: 39px;
         }

         .seal-text {
             line-height: 78px;
             /* لعمل توسيط عمودي للنص دون الحاجة للـ Flex/Table */
             color: #7d5bd8;
             font-weight: bold;
             font-size: 14px;
             letter-spacing: 1px;

             margin: 10;
         }
     </style>
 </head>

 <body>
     <!-- الإطارات الخارجية -->
     <div class="border-outer"></div>
     <div class="border-inner"></div>

     <!-- المحتوى -->
     <div class="content">
         <div class="brand">Learnova</div>

         <div class="cert-title">CERTIFICATE</div>
         <div class="cert-subtitle">OF COMPLETION</div>
         <div class="divider"></div>

         <div class="presented-to">This certificate is proudly presented to</div>
         <div class="student-name">{{ $student_name }}</div>

         <div class="course-label">For successfully completing the course</div>

         <!-- بادج الدورة -->
         <table class="badge-table">
             <tr>
                 <td>{{ $course_title }}</td>
             </tr>
         </table>

         <!-- الفوتر والتوقيعات (مقسم بدقة لـ 4 أجزاء متساوية لتجنب الاصطدام) -->
         <table class="footer-table">
             <tr>
                 <!-- توقيع المدرب -->
                 <td style="width: 25%;">
                     <h4 style="color: #6C3CF0">{{ $teacher_name }}</h4><br>
                     <div class="signature-line">Instructor</div>
                 </td>

                 <!-- الباركود والتفاصيل -->
                 <td style="width: 25%;">
                     <img class="qr-image"
                         src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode(url('/api/verify/' . $serial_number)) }}">
                     <div class="qr-text">
                         <strong>Certificate ID:</strong><br>{{ $serial_number }}<br>
                         <strong>Issue Date:</strong><br>{{ $issued_date }}
                     </div>
                 </td>


                 <!-- الختم -->
                 <td style="width: 25%;">
                     <div class="seal-outer">
                         <div class="seal-inner">
                             <p class="seal-text">Learnova</p>
                         </div>
                     </div>
                 </td>
             </tr>
         </table>
     </div>
 </body>

 </html>
