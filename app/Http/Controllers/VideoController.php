<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VideoController extends Controller
{
    // ─────────────────────────────────────────────────────────────────
    // CONFIG — naya frame.jpg 8000x4500 ka hai. In values ko bharo.
    // ─────────────────────────────────────────────────────────────────

    private const CIRCLE_X = 233;
    private const CIRCLE_Y = 2070;
    private const CIRCLE_W = 1592;
    private const CIRCLE_H = 1592;
    private const CIRCLE_RIM = 140;

    private const VIDEO_X = 1385;
    private const VIDEO_Y = 0;
    private const VIDEO_W = 6615;
    private const VIDEO_H = 3545;

    // Aapne jo diye the (8000x4500 space)
    private const TEXT_NAME     = ['x' => 3207, 'y' => 3545];
    private const TEXT_HOSPITAL = ['x' => 3212, 'y' => 3739];
    private const TEXT_MOBILE   = ['x' => 3216, 'y' => 3951];
    private const TEXT_DOCTOR   = ['x' => 372, 'y' => 3905];

    private const MASTER_W = 8000;
    private const MASTER_H = 4500;

    private const FONT_PATH = '';
    private const FONT_BOLD_PATH = '';
    private const FONT_REGULAR_PATH = '';

    private const LANGUAGE_VIDEOS = [
        'Assamese'  => 'video/utbiom-2-assamese.mp4',
        'Bengali'   => 'video/utbiom-2-bengali.mp4',
        'English'   => 'video/frame.mp4',
        'Gujarati'  => 'video/utbiom-2-gujarati.mp4',
        'Hindi'     => 'video/utbiom-2-hindi.mp4',
        'Kannada'   => 'video/utbiom-2-kannada.mp4',
        'Malayalam' => 'video/utbiom-2-malayalam.mp4',
        'Marathi'   => 'video/utbiom-2-marathi.mp4',
        'Odia'      => 'video/utbiom-2-odia.mp4',
        'Punjabi'   => 'video/utbiom-2-punjabi.mp4',
        'Tamil'     => 'video/utbiom-2-tamil.mp4',
        'Telugu'    => 'video/utbiom-2-telugu.mp4',
    ];

    // ─── S3 CONFIG ──────────────────────────────────────────────────
    // Sab kuch is bucket-folder ke andar jayega: sybella-patient-awareness-video/{uploads,banners,videos}
    private const S3_DISK   = 's3';
    private const S3_FOLDER = 'sybella-patient-awareness-video';

    public function index()
    {
        $employeeId = auth()->id();

        $doctors = User::where('parent_id', $employeeId)
            ->where('type', 'doctor')
            ->get(['id', 'name', 'msl_number']);

        $languages = array_keys(self::LANGUAGE_VIDEOS);

        return view('video.index', compact('doctors', 'languages'));
    }

    public function store(Request $request)
    {
        set_time_limit(300);
        ini_set('max_execution_time', 300);
        // 8000x4500 truecolor image akela ~144MB leta hai, aur is process me
        // ek saath kai aisi images (frame, frameLayer, photo, resized) memory me
        // hoti hain — isliye default 512M limit todna aam baat hai.
        ini_set('memory_limit', '2048M');

        $request->validate([
            'doctor_id'        => 'required',
            'doctor_name'      => 'required|string|max:255',
            'mobile'           => 'required',
            'speciality'       => 'required',
            'hospital_name'    => 'required',
            'hospital_address' => 'required',
            'language'         => ['required', Rule::in(array_keys(self::LANGUAGE_VIDEOS))],
            'photo'            => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $doctor = User::where('id', $request->doctor_id)
            ->where('parent_id', auth()->id())
            ->firstOrFail();

        $frameMp4 = $this->resolveLanguageVideo($request->language);

        // ─────────────────────────────────────────────────────────────
        // Sab kuch pehle LOCAL scratch folder me generate hota hai
        // (GD aur FFmpeg dono ko real file path chahiye — S3 se seedha
        // kaam nahi karte). Aakhir me photo/banner/video S3 pe upload
        // karke local temp copies delete kar denge.
        // ─────────────────────────────────────────────────────────────
        $tempDir = storage_path('app/tmp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $photoExt      = strtolower($request->file('photo')->getClientOriginalExtension());
        $photoName     = 'photo_' . time() . '_' . uniqid() . '.' . $photoExt;
        $photoFullPath = $tempDir . '/' . $photoName;
        $request->file('photo')->move($tempDir, $photoName);

        $framePng = public_path('video/frame.png'); // transparent foreground frame, app asset

        $fontBold    = $this->resolveFontPath('bold');
        $fontRegular = $this->resolveFontPath('regular');

        // ─── Banner Generate (GD, full high-res 8000x4500) ────────────
        // Banner download is disabled, so skip its expensive 8000x4500 render.
        if (false) {
        $bannerName = 'banner_' . time() . '.png';
        $bannerPath = $tempDir . '/' . $bannerName;

        $frameLayer = imagecreatefrompng($framePng);
        $frame = imagecreatetruecolor(self::MASTER_W, self::MASTER_H);
        $white = imagecolorallocate($frame, 255, 255, 255);
        imagefill($frame, 0, 0, $white);
        $photo = ($photoExt === 'png')
            ? imagecreatefrompng($photoFullPath)
            : imagecreatefromjpeg($photoFullPath);

        $resized = imagecreatetruecolor(self::CIRCLE_W, self::CIRCLE_H);
        imagecopyresampled($resized, $photo, 0, 0, 0, 0, self::CIRCLE_W, self::CIRCLE_H, imagesx($photo), imagesy($photo));
        // Copy only the ellipse, so the banner photo never leaks outside the circle.
        $radiusX = self::CIRCLE_W / 2;
        $radiusY = self::CIRCLE_H / 2;
        for ($y = 0; $y < self::CIRCLE_H; $y++) {
            $normalizedY = ($y - $radiusY) / $radiusY;
            $halfWidth = (int) floor($radiusX * sqrt(max(0, 1 - ($normalizedY * $normalizedY))));
            $startX = (int) $radiusX - $halfWidth;
            $rowWidth = max(1, 2 * $halfWidth);
            imagecopy($frame, $resized, self::CIRCLE_X + $startX, self::CIRCLE_Y + $y, $startX, $y, $rowWidth, 1);
        }
        imagealphablending($frame, true);
        imagecopy($frame, $frameLayer, 0, 0, 0, 0, self::MASTER_W, self::MASTER_H);

        $black = imagecolorallocate($frame, 30, 30, 30);
        $this->drawBannerText($frame, $fontBold, 'Dr. ' . ($doctor->name ?? ''), self::TEXT_DOCTOR, $black, 55);
        $this->drawBannerText($frame, $fontBold, 'Name :', ['x' => 2700, 'y' => self::TEXT_NAME['y']], $black, 42);
        $this->drawBannerText($frame, $fontBold, 'Hospital :', ['x' => 2700, 'y' => self::TEXT_HOSPITAL['y']], $black, 42);
        $this->drawBannerText($frame, $fontBold, 'Mobile :', ['x' => 2700, 'y' => self::TEXT_MOBILE['y']], $black, 42);
        $this->drawBannerText($frame, $fontRegular, $doctor->name ?? '', self::TEXT_NAME, $black);
        $this->drawBannerText($frame, $fontRegular, $request->hospital_name, self::TEXT_HOSPITAL, $black);
        $this->drawBannerText($frame, $fontRegular, $request->mobile, self::TEXT_MOBILE, $black);
        imagesetthickness($frame, 5);
        imageline($frame, self::TEXT_NAME['x'], self::TEXT_NAME['y'] + 95, 5600, self::TEXT_NAME['y'] + 95, $black);
        imageline($frame, self::TEXT_HOSPITAL['x'], self::TEXT_HOSPITAL['y'] + 95, 5600, self::TEXT_HOSPITAL['y'] + 95, $black);
        imageline($frame, self::TEXT_MOBILE['x'], self::TEXT_MOBILE['y'] + 95, 5600, self::TEXT_MOBILE['y'] + 95, $black);

        // Faster lossless compression for this very large 8000x4500 banner.
        imagepng($frame, $bannerPath, 1);
        imagedestroy($frame);
        imagedestroy($frameLayer);
        imagedestroy($photo);
        imagedestroy($resized);
        }

        // ─── Detect real target resolution from frame.mp4 ─────────────
        exec(
            "ffprobe -v error -select_streams v:0 -show_entries stream=width,height -of csv=p=0:s=x "
            . escapeshellarg($frameMp4),
            $dimOut
        );
        $dims = array_pad(explode('x', trim($dimOut[0] ?? '848x480')), 2, null);
        $sourceW = (int) ($dims[0] ?: 848);
        $sourceH = (int) ($dims[1] ?: 480);
        // 540p is a good mobile/web balance and leaves enough headroom for the
        // complete request (render + S3 upload) to finish near the 1-minute goal.
        $outW = min(960, $sourceW);
        $outH = (int) round($sourceH * ($outW / $sourceW));
        $outH -= $outH % 2;

        // ─── Cached small background — LOCAL ONLY, generated ONCE, reused ──
        // Ye purely performance ke liye hai (8000x4500 baar baar decode na
        // ho). Ye kabhi S3 pe nahi jaata — koi user isse access nahi karta.
        $cacheDir = storage_path('app/cache');
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }
        // Uploaded photo is a transparent circular layer. frame.png supplies the rim/design above it.
        // Ye bhi LOCAL cache hai (ffmpeg input ke liye), S3 pe nahi jaata.
        $circleOverlay = $cacheDir . '/circle_photo_' . md5($photoFullPath . filemtime($photoFullPath))
            . "_{$outW}x{$outH}.png";
        if (!file_exists($circleOverlay)) {
            $this->createCirclePhotoOverlay($photoFullPath, $circleOverlay, $outW, $outH);
        }

        // ─── Scale factors: 8000x4500 → outW x outH ────────────────────
        $scaleX = $outW / self::MASTER_W;
        $scaleY = $outH / self::MASTER_H;

        $vx = (int) round(self::VIDEO_X * $scaleX);
        $vy = (int) round(self::VIDEO_Y * $scaleY);
        $vw = (int) round(self::VIDEO_W * $scaleX);
        // A tiny bottom overlap hides the white seam caused by scaled rounding.
        $vh = (int) round(self::VIDEO_H * $scaleY) + 2;

        $tName     = ['x' => (int) round(self::TEXT_NAME['x'] * $scaleX),     'y' => (int) round(self::TEXT_NAME['y'] * $scaleY)];
        $tHospital = ['x' => (int) round(self::TEXT_HOSPITAL['x'] * $scaleX), 'y' => (int) round(self::TEXT_HOSPITAL['y'] * $scaleY)];
        $tMobile   = ['x' => (int) round(self::TEXT_MOBILE['x'] * $scaleX),   'y' => (int) round(self::TEXT_MOBILE['y'] * $scaleY)];
        $tDoctor   = ['x' => (int) round(self::TEXT_DOCTOR['x'] * $scaleX),   'y' => (int) round(self::TEXT_DOCTOR['y'] * $scaleY)];
        $labelX = (int) round(2700 * $scaleX);
        $lineEndX = (int) round(5600 * $scaleX);
        $lineOffset = max(2, (int) round(125 * $scaleY));
        $lineThickness = max(1, (int) round(2 * ($outH / 1080)));

        // The previous fixed 1080p font sizes became oversized at 540p.
        $textScale = $outH / 1080;
        $doctorFontSize = max(12, (int) round(25 * $textScale));
        $labelFontSize = max(10, (int) round(20 * $textScale));
        $nameFontSize = max(12, (int) round(25 * $textScale));
        $detailFontSize = max(11, (int) round(22 * $textScale));

        // ─── Video Generate (FFmpeg) ────────────────────────────────────
        $videoName = 'video_' . time() . '.mp4';
        $videoPath = $tempDir . '/' . $videoName;

        exec(
            "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 "
            . escapeshellarg($frameMp4),
            $probeOut
        );
        $mp4Duration   = (float) ($probeOut[0] ?? 5);
        $totalDuration = $mp4Duration;

        $fontBoldEsc    = $this->ffmpegEscape($fontBold);
        $fontRegularEsc = $this->ffmpegEscape($fontRegular);
        $nameEsc     = $this->ffmpegEscape($request->doctor_name);
        $hospitalEsc = $this->ffmpegEscape($request->hospital_name);
        $mobileEsc   = $this->ffmpegEscape($request->mobile);
        $doctorLabelEsc = $this->ffmpegEscape('Dr. ' . $request->doctor_name);
        $nameLabelEsc = $this->ffmpegEscape('Name :');
        $hospitalLabelEsc = $this->ffmpegEscape('Hospital :');
        $mobileLabelEsc = $this->ffmpegEscape('Mobile :');

        // input 0 = frame.mp4, full moving background (video + audio)
        // input 1 = uploaded circular photo
        // input 2 = transparent frame.png, always on top
        $filter =
            "color=c=white:s={$outW}x{$outH}:d={$totalDuration}[base];"
            . "[0:v]scale={$vw}:{$vh}:force_original_aspect_ratio=increase,crop={$vw}:{$vh},setsar=1[panelvideo];"
            . "[base][panelvideo]overlay={$vx}:{$vy}:shortest=1[bgvideo];"
            . "[1:v]scale={$outW}:{$outH},setsar=1[photo];"
            . "[2:v]scale={$outW}:{$outH},format=rgba,setsar=1[frame];"
            . "[bgvideo][photo]overlay=0:0[withphoto];"
            . "[withphoto][frame]overlay=0:0[framed];"
            . "[framed]drawtext=fontfile='{$fontBoldEsc}':text='{$doctorLabelEsc}':x={$tDoctor['x']}:y={$tDoctor['y']}:fontsize={$doctorFontSize}:fontcolor=black,"
            .   "drawtext=fontfile='{$fontBoldEsc}':text='{$nameLabelEsc}':x={$labelX}:y={$tName['y']}:fontsize={$labelFontSize}:fontcolor=black,"
            .   "drawtext=fontfile='{$fontBoldEsc}':text='{$hospitalLabelEsc}':x={$labelX}:y={$tHospital['y']}:fontsize={$labelFontSize}:fontcolor=black,"
            .   "drawtext=fontfile='{$fontBoldEsc}':text='{$mobileLabelEsc}':x={$labelX}:y={$tMobile['y']}:fontsize={$labelFontSize}:fontcolor=black,"
            .   "drawtext=fontfile='{$fontRegularEsc}':text='{$nameEsc}':x={$tName['x']}:y={$tName['y']}:fontsize={$nameFontSize}:fontcolor=black,"
            .   "drawtext=fontfile='{$fontRegularEsc}':text='{$hospitalEsc}':x={$tHospital['x']}:y={$tHospital['y']}:fontsize={$detailFontSize}:fontcolor=black,"
            .   "drawtext=fontfile='{$fontRegularEsc}':text='{$mobileEsc}':x={$tMobile['x']}:y={$tMobile['y']}:fontsize={$detailFontSize}:fontcolor=black,"
            .   "drawbox=x={$tName['x']}:y=" . ($tName['y'] + $lineOffset) . ":w=" . ($lineEndX - $tName['x']) . ":h={$lineThickness}:color=black:t=fill,"
            .   "drawbox=x={$tHospital['x']}:y=" . ($tHospital['y'] + $lineOffset) . ":w=" . ($lineEndX - $tHospital['x']) . ":h={$lineThickness}:color=black:t=fill,"
            .   "drawbox=x={$tMobile['x']}:y=" . ($tMobile['y'] + $lineOffset) . ":w=" . ($lineEndX - $tMobile['x']) . ":h={$lineThickness}:color=black:t=fill[composed];"
            . "[composed]fps=20[outv]";

        // -map 0:a? = frame.mp4 ka audio track carry karo (agar ho to; '?'
        // se agar audio stream na ho tab bhi ffmpeg error nahi dega).
        // Pehle sirf -map "[outv]" tha, jisse audio silently drop ho raha tha.
        $command = "ffmpeg -y "
            . "-i " . escapeshellarg($frameMp4) . " "
            . "-i " . escapeshellarg($circleOverlay) . " "
            . "-i " . escapeshellarg($framePng) . " "
            . "-filter_complex \"{$filter}\" "
            . "-map \"[outv]\" -map 0:a? "
            // CRF 29 substantially reduces the upload size/time while retaining
            // good 1080p quality. The source AAC track can be copied unchanged.
            . "-c:v libx264 -preset ultrafast -crf 29 -threads 0 "
            . "-c:a copy "
            . "-pix_fmt yuv420p "
            . "-t {$totalDuration} "
            . escapeshellarg($videoPath);

        exec($command . " 2>&1", $ffmpegOutput, $returnCode);

        // ─────────────────────────────────────────────────────────────
        // S3 pe upload — photo, banner, video. Sab sybella-patient-awareness-video/
        // folder ke andar jaate hain (photo -> uploads/, banner -> banners/, video -> videos/).
        // ─────────────────────────────────────────────────────────────
        $photoS3Key  = self::S3_FOLDER . '/uploads/' . $photoName;
        $videoS3Key  = self::S3_FOLDER . '/videos/' . $videoName;

        $this->uploadFile($photoS3Key, $photoFullPath);

        if (file_exists($videoPath) && $returnCode === 0) {
            $this->uploadFile($videoS3Key, $videoPath);
        } else {
            Log::warning('VideoController: ffmpeg video generation failed, skipping S3 upload.', [
                'returnCode' => $returnCode,
                'output'     => $ffmpegOutput,
            ]);
        }

        // Local temp cleanup — sirf ye teen files (cache folder ko chhodo)
        @unlink($photoFullPath);
        @unlink($videoPath);

        // ─── Update Doctor ────────────────────────────────────
        $doctor->update([
            'name'          => $request->doctor_name,
            'mobile'        => $request->mobile,
            'speciality'    => $request->speciality,
            'hospital_name' => $request->hospital_name,
            'address'       => $request->hospital_address,
            'profile_image' => $photoS3Key,
            'language'      => $request->language,
        ]);

        // ─── Save DB ──────────────────────────────────────────
        $userFile = UserFile::create([
            'user_id'     => $doctor->id,
            'photo'       => $photoS3Key,
            'banner_path' => null,
            'video'       => $videoS3Key,
            'language'    => $request->language,
        ]);

        return redirect()->route('video.index')->with([
            'generated'    => true,
            'user_file_id' => $userFile->id,
        ]);
    }

    public function downloadBanner($id)
    {
        $file = UserFile::findOrFail($id);
        $url  = Storage::disk(self::S3_DISK)->temporaryUrl(
            $file->banner_path,
            now()->addMinutes(10),
            [
                'ResponseContentDisposition' => 'attachment; filename="' . basename($file->banner_path) . '"',
            ]
        );
        return redirect($url);
    }

    public function downloadVideo($id)
    {
        $file = UserFile::with('doctor:id,name')->findOrFail($id);
        $doctorName = Str::slug($file->doctor?->name ?? '');
        $downloadName = ($doctorName ?: 'doctor-' . $file->user_id) . '.mp4';

        $url  = Storage::disk(self::S3_DISK)->temporaryUrl(
            $file->video,
            now()->addMinutes(10),
            [
                'ResponseContentDisposition' => 'attachment; filename="' . $downloadName . '"',
            ]
        );
        return redirect($url);
    }

    // ── Helpers ─────────────────────────────────────────────────────

    private function uploadFile(string $s3Key, string $localPath): void
    {
        $stream = fopen($localPath, 'rb');
        if ($stream === false) {
            throw new \RuntimeException("Unable to open file for upload: {$localPath}");
        }

        try {
            Storage::disk(self::S3_DISK)->put($s3Key, $stream);
        } finally {
            fclose($stream);
        }
    }

    private function createCirclePhotoOverlay(string $photoPath, string $outputPath, int $outW, int $outH): void
    {
        $extension = strtolower(pathinfo($photoPath, PATHINFO_EXTENSION));
        $photo = $extension === 'png' ? imagecreatefrompng($photoPath) : imagecreatefromjpeg($photoPath);
        $overlay = imagecreatetruecolor($outW, $outH);
        imagealphablending($overlay, false);
        imagesavealpha($overlay, true);
        $transparent = imagecolorallocatealpha($overlay, 0, 0, 0, 127);
        imagefill($overlay, 0, 0, $transparent);

        $scaleX = $outW / self::MASTER_W;
        $scaleY = $outH / self::MASTER_H;
        $circleX = (int) round(self::CIRCLE_X * $scaleX);
        $circleY = (int) round(self::CIRCLE_Y * $scaleY);
        $circleW = (int) round(self::CIRCLE_W * $scaleX);
        $circleH = (int) round(self::CIRCLE_H * $scaleY);

        $photoW = imagesx($photo);
        $photoH = imagesy($photo);
        $sourceRatio = $photoW / $photoH;
        $targetRatio = $circleW / $circleH;
        if ($sourceRatio > $targetRatio) {
            $cropH = $photoH;
            $cropW = (int) round($photoH * $targetRatio);
            $cropX = (int) round(($photoW - $cropW) / 2);
            $cropY = 0;
        } else {
            $cropW = $photoW;
            $cropH = (int) round($photoW / $targetRatio);
            $cropX = 0;
            $cropY = (int) round(($photoH - $cropH) / 2);
        }

        $fitted = imagecreatetruecolor($circleW, $circleH);
        imagecopyresampled($fitted, $photo, 0, 0, $cropX, $cropY, $circleW, $circleH, $cropW, $cropH);
        imagealphablending($overlay, true);
        $cx = $circleW / 2;
        $cy = $circleH / 2;
        for ($y = 0; $y < $circleH; $y++) {
            for ($x = 0; $x < $circleW; $x++) {
                $inside = (($x - $cx) ** 2) / ($cx ** 2) + (($y - $cy) ** 2) / ($cy ** 2) <= 1;
                if ($inside) {
                    imagesetpixel($overlay, $circleX + $x, $circleY + $y, imagecolorat($fitted, $x, $y));
                }
            }
        }

        imagepng($overlay, $outputPath, 1);
        imagedestroy($fitted);
        imagedestroy($photo);
        imagedestroy($overlay);
    }

    private function createCircleOverlay(string $backgroundPath, string $photoPath, string $outputPath, int $outW, int $outH): void
    {
        $background = imagecreatefromjpeg($backgroundPath);
        $extension = strtolower(pathinfo($photoPath, PATHINFO_EXTENSION));
        $photo = $extension === 'png' ? imagecreatefrompng($photoPath) : imagecreatefromjpeg($photoPath);
        $overlay = imagecreatetruecolor($outW, $outH);
        imagealphablending($overlay, false);
        imagesavealpha($overlay, true);
        $transparent = imagecolorallocatealpha($overlay, 0, 0, 0, 127);
        imagefill($overlay, 0, 0, $transparent);

        $scaleX = $outW / self::MASTER_W;
        $scaleY = $outH / self::MASTER_H;
        $rimX = (int) round(self::CIRCLE_RIM * $scaleX);
        $rimY = (int) round(self::CIRCLE_RIM * $scaleY);
        $innerX = (int) round(self::CIRCLE_X * $scaleX);
        $innerY = (int) round(self::CIRCLE_Y * $scaleY);
        $innerW = (int) round(self::CIRCLE_W * $scaleX);
        $innerH = (int) round(self::CIRCLE_H * $scaleY);
        $outerX = max(0, $innerX - $rimX);
        $outerY = max(0, $innerY - $rimY);
        $outerW = $innerW + (2 * $rimX);
        $outerH = $innerH + (2 * $rimY);
        $outerCx = $outerX + ($outerW / 2);
        $outerCy = $outerY + ($outerH / 2);
        $innerCx = $innerX + ($innerW / 2);
        $innerCy = $innerY + ($innerH / 2);

        // Cover-crop the uploaded photo instead of stretching it.
        $photoW = imagesx($photo);
        $photoH = imagesy($photo);
        $sourceRatio = $photoW / $photoH;
        $targetRatio = $outerW / $outerH;
        if ($sourceRatio > $targetRatio) {
            $cropH = $photoH;
            $cropW = (int) round($photoH * $targetRatio);
            $cropX = (int) round(($photoW - $cropW) / 2);
            $cropY = 0;
        } else {
            $cropW = $photoW;
            $cropH = (int) round($photoW / $targetRatio);
            $cropX = 0;
            $cropY = (int) round(($photoH - $cropH) / 2);
        }
        $fittedPhoto = imagecreatetruecolor($outerW, $outerH);
        imagecopyresampled($fittedPhoto, $photo, 0, 0, $cropX, $cropY, $outerW, $outerH, $cropW, $cropH);

        imagealphablending($overlay, true);
        for ($y = $outerY; $y < min($outH, $outerY + $outerH); $y++) {
            for ($x = $outerX; $x < min($outW, $outerX + $outerW); $x++) {
                $inOuter = (($x - $outerCx) ** 2) / (($outerW / 2) ** 2)
                    + (($y - $outerCy) ** 2) / (($outerH / 2) ** 2) <= 1;
                $inInner = (($x - $innerCx) ** 2) / (($innerW / 2) ** 2)
                    + (($y - $innerCy) ** 2) / (($innerH / 2) ** 2) < 1;

                if ($inOuter) {
                    // Photo continues below the complete rim, including its design gaps.
                    imagesetpixel($overlay, $x, $y, imagecolorat($fittedPhoto, $x - $outerX, $y - $outerY));
                }

                if ($inOuter && !$inInner) {
                    $frameColor = imagecolorat($background, $x, $y);
                    $red = ($frameColor >> 16) & 0xFF;
                    $green = ($frameColor >> 8) & 0xFF;
                    $blue = $frameColor & 0xFF;
                    $isWhitePatch = $red > 225 && $green > 225 && $blue > 225;
                    if (!$isWhitePatch) {
                        imagesetpixel($overlay, $x, $y, $frameColor);
                    }
                }
            }
        }

        // Restore the complete lower frame and the red curved top edge above the video.
        $solidFrameY = (int) round(3545 * $scaleY);
        $redEdgeY = (int) round(3150 * $scaleY);
        imagecopy($overlay, $background, 0, $solidFrameY, 0, $solidFrameY, $outW, $outH - $solidFrameY);
        for ($y = $redEdgeY; $y < $solidFrameY; $y++) {
            for ($x = 0; $x < $outW; $x++) {
                $frameColor = imagecolorat($background, $x, $y);
                $red = ($frameColor >> 16) & 0xFF;
                $green = ($frameColor >> 8) & 0xFF;
                $blue = $frameColor & 0xFF;
                if ($red > 150 && $red > ($green * 1.25) && $red > ($blue * 1.10)) {
                    imagesetpixel($overlay, $x, $y, $frameColor);
                }
            }
        }

        imagepng($overlay, $outputPath, 6);
        imagedestroy($overlay);
        imagedestroy($background);
        imagedestroy($photo);
        imagedestroy($fittedPhoto);
    }

    private function drawBannerText($image, string $fontPath, string $text, array $pos, int $color, float $size = 45): void
    {
        if (!file_exists($fontPath)) {
            // fallback so banner generation doesn't silently break agar font missing ho
            imagestring($image, 5, $pos['x'], $pos['y'], $text, $color);
            return;
        }
        imagettftext($image, $size, 0, $pos['x'], $pos['y'], $color, $fontPath, $text);
    }

    private function resolveLanguageVideo(string $language): string
    {
        $relativePath = self::LANGUAGE_VIDEOS[$language] ?? null;
        $fullPath = $relativePath ? public_path($relativePath) : null;

        if (!$fullPath || !is_file($fullPath)) {
            throw ValidationException::withMessages([
                'language' => "{$language} video file not found in public/video.",
            ]);
        }

        return $fullPath;
    }

    private function resolveFontPath(string $weight = 'bold'): string
    {
        $overridePath = $weight === 'regular'
            ? (self::FONT_REGULAR_PATH ?: self::FONT_PATH)
            : (self::FONT_BOLD_PATH ?: self::FONT_PATH);

        $candidates = $weight === 'regular'
            ? array_filter([
                $overridePath ?: null,
                public_path('fonts/Poppins-Bold.ttf'),
                public_path('fonts/HvDTrial_Brandon_Grotesque_regular-BF64a625c9311e1.otf'),
                public_path('fonts/HvDTrial_Brandon_Grotesque_bold-BF64a625c9151d5.otf'),
                '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
                '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
            ])
            : array_filter([
                $overridePath ?: null,
                public_path('fonts/Poppins-Bold.ttf'),
                public_path('fonts/HvDTrial_Brandon_Grotesque_bold-BF64a625c9151d5.otf'),
                public_path('fonts/HvDTrial_Brandon_Grotesque_regular-BF64a625c9311e1.otf'),
                '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
                '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
            ]);

        foreach ($candidates as $path) {
            if ($path && file_exists($path)) {
                return $path;
            }
        }


        Log::warning("VideoController: no TTF/OTF font found for weight '{$weight}' — set FONT_BOLD_PATH/FONT_REGULAR_PATH to a real font file. Banner/video text will not render correctly.");

        return '';
    }

    private function ffmpegEscape(string $value): string
    {
        $value = str_replace('\\', '/', $value);
        $value = str_replace(':', '\\:', $value);
        $value = str_replace("'", "\\'", $value);
        return $value;
    }
}
