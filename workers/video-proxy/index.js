export default {
  async fetch(request, env) {
    // 1. معالجة طلبات الـ CORS Preflight (OPTIONS)
    if (request.method === "OPTIONS") {
      return new Response(null, {
        headers: {
          "Access-Control-Allow-Origin": "*",
          "Access-Control-Allow-Methods": "GET, HEAD, OPTIONS",
          "Access-Control-Allow-Headers": "*",
          "Access-Control-Max-Age": "86400",
        },
      });
    }

    const corsHeaders = {
      "Access-Control-Allow-Origin": "*",
      "Access-Control-Allow-Methods": "GET, HEAD, OPTIONS",
      "Access-Control-Allow-Headers": "*",
    };

    try {
      const url = new URL(request.url);
      const B2_KEY_ID = env.B2_KEY_ID;
      const B2_APPLICATION_KEY = env.B2_APPLICATION_KEY;
      const B2_ENDPOINT = env.B2_ENDPOINT;
      const B2_BUCKET = env.B2_BUCKET;


      if (!B2_KEY_ID || !B2_APPLICATION_KEY || !B2_ENDPOINT || !B2_BUCKET) {
        return new Response("❌ خطأ: المتغيرات السرية ناقصة بالـ Worker Environment", { status: 500, headers: corsHeaders });
      }

      const host = B2_BUCKET + "." + B2_ENDPOINT;
      const s3Url = "https://" + host + url.pathname + url.search;

      const now = new Date();
      const datetime = now.toISOString().replace(/[:-]/g, "").split(".")[0] + "Z";
      const datestamp = datetime.substr(0, 8);
      const region = B2_ENDPOINT.split(".")[1];
      const service = "s3";
      const payloadHash = "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855";

      const headersToSign = [
        { name: "host", value: host.toLowerCase().trim() },
        { name: "x-amz-content-sha256", value: payloadHash },
        { name: "x-amz-date", value: datetime }
      ];

      if (request.headers.has("Range")) {
        headersToSign.push({ name: "range", value: request.headers.get("Range").trim() });
      }

      headersToSign.sort((a, b) => a.name.localeCompare(b.name));

      const canonicalHeaders = headersToSign.map(h => h.name + ":" + h.value + "\n").join("");
      const signedHeaders = headersToSign.map(h => h.name).join(";");
      const canonicalUri = encodeURI(url.pathname).replace(/[!'()*]/g, c => "%" + c.charCodeAt(0).toString(16).toUpperCase());
      const canonicalQueryString = [...url.searchParams.entries()].map(([k, v]) => encodeURIComponent(k) + "=" + encodeURIComponent(v)).sort().join("&");

      const canonicalRequest = [request.method, canonicalUri, canonicalQueryString, canonicalHeaders, signedHeaders, payloadHash].join("\n");
      const canonicalRequestHash = await sha256(canonicalRequest);
      const credentialScope = datestamp + "/" + region + "/" + service + "/aws4_request";

      const stringToSign = ["AWS4-HMAC-SHA256", datetime, credentialScope, canonicalRequestHash].join("\n");
      const signingKey = await getSignatureKey(B2_APPLICATION_KEY, datestamp, region, service);
      const signatureBuffer = await hmac(signingKey, stringToSign);
      const signature = bufferToHex(signatureBuffer);

      const authorizationHeader = "AWS4-HMAC-SHA256 Credential=" + B2_KEY_ID + "/" + credentialScope + ", SignedHeaders=" + signedHeaders + ", Signature=" + signature;

      const fetchHeaders = new Headers();
      fetchHeaders.set("Host", host);
      fetchHeaders.set("X-Amz-Date", datetime);
      fetchHeaders.set("X-Amz-Content-Sha256", payloadHash);
      fetchHeaders.set("Authorization", authorizationHeader);

      if (request.headers.has("Range")) {
        fetchHeaders.set("Range", request.headers.get("Range"));
      }

      // 🕵️‍♂️ جلب الملف الفعلي من Backblaze B2
      let response = await fetch(s3Url, { method: request.method, headers: fetchHeaders });

      // 📊 الـ Logging الموحد (المرحلة 0) لمراقبة استقرار الـ Traffic والـ Range Headers
      const rangeHeaderLog = request.headers.get("Range") || "None";
      console.log(`[ACCESS LOG] Path: ${url.pathname} | Method: ${request.method} | Status: ${response.status} | Range: ${rangeHeaderLog}`);
      // 🚨 تسجل جنائي مفصل فقط لو رجع خطأ من B2 (مثل 403 أو 404) مع إصلاح الـ Template Literals
      if (!response.ok) {
        const errorBody = await response.clone().text();
        console.log(`🚨 [B2 ERROR] Status: ${response.status} | Path: ${url.pathname}`);
        console.log(`🚨 [B2 ERROR BODY]: ${errorBody}`);
      }

      // محاولة الحفظ بـ Cache الـ Cloudflare (معزول لحمايته على الـ Free Subdomains)
      try {
        if (url.pathname.endsWith(".ts") && response.status === 200 && caches && caches.default) {
          const cacheResponse = response.clone();
          cacheResponse.headers.set("Cache-Control", "public, max-age=31536000");
          await caches.default.put(request, cacheResponse);
        }
      } catch (cacheErr) {
        console.log(`⚠️ Cache API Warning (Safe to ignore on workers.dev): ${cacheErr.message}`);
      }

      // دمج وتثبيت هيدرات الـ CORS للاستجابة الراجعة للمتصفح
      const responseHeaders = new Headers(response.headers);
      for (const [key, value] of Object.entries(corsHeaders)) {
        responseHeaders.set(key, value);
      }

      return new Response(response.body, { status: response.status, statusText: response.statusText, headers: responseHeaders });

    } catch (err) {
      // 💥 تسجيل الـ Runtime Crash الداخلي للـ Worker في حال انهار الكود
      console.log(`💥 [WORKER CRASH]: ${err.message}`);
      return new Response("❌ خطأ داخلي في الـ Worker: " + err.message, { status: 500, headers: corsHeaders });
    }
  }
};

// الدوال المساعدة الثابتة للتوقيع والـ Cryptography
const encoder = new TextEncoder();
async function sha256(message) { const msgBuffer = encoder.encode(message); const hashBuffer = await crypto.subtle.digest("SHA-256", msgBuffer); return Array.from(new Uint8Array(hashBuffer)).map(b => b.toString(16).padStart(2, "0")).join(""); }
async function hmac(key, message) { const keyBuffer = typeof key === "string" ? encoder.encode(key) : key; const messageBuffer = encoder.encode(message); const cryptoKey = await crypto.subtle.importKey("raw", keyBuffer, { name: "HMAC", hash: "SHA-256" }, false, ["sign"]); const signature = await crypto.subtle.sign("HMAC", cryptoKey, messageBuffer); return new Uint8Array(signature); }
async function getSignatureKey(key, dateStamp, regionName, serviceName) { const kDate = await hmac("AWS4" + key, dateStamp); const kRegion = await hmac(kDate, regionName); const kService = await hmac(kRegion, serviceName); const kSigning = await hmac(kService, "aws4_request"); return kSigning; }
function bufferToHex(buffer) { return Array.from(buffer).map(b => b.toString(16).padStart(2, "0")).join(""); }
