<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class VideoController extends Controller
{
    // ─────────────────────────────────────────────────────────────────
    // CONFIG — naya frame.jpg 8000x4500 ka hai. In values ko bharo.
    // ─────────────────────────────────────────────────────────────────

    // TODO: Photoshop se circle (photo/video area) ke exact bounds nikalo
    // aur yaha daalo. Jab tak ye 0 hai, code chalega nahi (crash karega).
    private const CIRCLE_X = 233;
    private const CIRCLE_Y = 2070;
    private const CIRCLE_W = 1592;
    private const CIRCLE_H = 1592;
    private const CIRCLE_RIM = 140;

    // Video (moving clip) ka area circle se alag/bada hai.
    // TODO: VIDEO_Y confirm karo Photoshop se — abhi 0 placeholder hai.
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

    private const LANGUAGE_VIDEOS = [
        'English' => 'video/frame.mp4',
        'Hindi' => 'video/frame.mp4',
        'Marathi' => 'video/frame.mp4',
    ];

    public function index()
    {
        $employeeId = auth()->id();

        $doctors = User::where('parent_id', $employeeId)
            ->where('type', 'doctor')
            ->get();

        return view('video.index', compact('doctors'));
    }

    public function store(Request $request)
    {
        set_time_limit(300);
        ini_set('max_execution_time', 300);

        $request->validate([
            'doctor_id'        => 'required',
            'mobile'           => 'required',
            'speciality'       => 'required',
            'hospital_name'    => 'required',
            'hospital_address' => 'required',
            'language'         => 'required|in:English,Hindi,Marathi',
            'photo'            => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $doctor = User::where('id', $request->doctor_id)
            ->where('parent_id', auth()->id())
            ->firstOrFail();

        $frameMp4 = $this->resolveLanguageVideo($request->language);

        $photoPath     = $request->file('photo')->store('uploads', 'public');
        $photoFullPath = storage_path('app/public/' . $photoPath);

        $frameJpg = public_path('video/frame.jpg'); // master, high-res (8000x4500)
        $framePng = public_path('video/frame.png'); // transparent foreground frame

        $fontPath = $this->resolveFontPath();

        // ─── Banner Generate (GD, full high-res 8000x4500) ────────────
        if (!is_dir(storage_path('app/public/banners'))) {
            mkdir(storage_path('app/public/banners'), 0777, true);
        }

        $bannerName = 'banner_' . time() . '.png';
        $bannerPath = storage_path('app/public/banners/' . $bannerName);

        $frameLayer = imagecreatefrompng($framePng);
        $frame = imagecreatetruecolor(self::MASTER_W, self::MASTER_H);
        $white = imagecolorallocate($frame, 255, 255, 255);
        imagefill($frame, 0, 0, $white);
        $ext   = strtolower(pathinfo($photoFullPath, PATHINFO_EXTENSION));
        $photo = ($ext === 'png')
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
        $this->drawBannerText($frame, $fontPath, 'Dr. ' . ($doctor->name ?? ''), self::TEXT_DOCTOR, $black, 55);
        $this->drawBannerText($frame, $fontPath, 'Name :', ['x' => 2700, 'y' => self::TEXT_NAME['y']], $black, 42);
        $this->drawBannerText($frame, $fontPath, 'Hospital :', ['x' => 2700, 'y' => self::TEXT_HOSPITAL['y']], $black, 42);
        $this->drawBannerText($frame, $fontPath, 'Mobile :', ['x' => 2700, 'y' => self::TEXT_MOBILE['y']], $black, 42);
        $this->drawBannerText($frame, $fontPath, $doctor->name ?? '', self::TEXT_NAME, $black);
        $this->drawBannerText($frame, $fontPath, $request->hospital_name, self::TEXT_HOSPITAL, $black);
        $this->drawBannerText($frame, $fontPath, $request->mobile, self::TEXT_MOBILE, $black);
        imagesetthickness($frame, 5);
        imageline($frame, self::TEXT_NAME['x'], self::TEXT_NAME['y'] + 95, 5600, self::TEXT_NAME['y'] + 95, $black);
        imageline($frame, self::TEXT_HOSPITAL['x'], self::TEXT_HOSPITAL['y'] + 95, 5600, self::TEXT_HOSPITAL['y'] + 95, $black);
        imageline($frame, self::TEXT_MOBILE['x'], self::TEXT_MOBILE['y'] + 95, 5600, self::TEXT_MOBILE['y'] + 95, $black);

        imagepng($frame, $bannerPath, 6);
        imagedestroy($frame);
        imagedestroy($frameLayer);
        imagedestroy($photo);
        imagedestroy($resized);

        // ─── Detect real target resolution from frame.mp4 ─────────────
        exec(
            "ffprobe -v error -select_streams v:0 -show_entries stream=width,height -of csv=p=0:s=x "
            . escapeshellarg($frameMp4),
            $dimOut
        );
        $dims = array_pad(explode('x', trim($dimOut[0] ?? '848x480')), 2, null);
        $outW = (int) ($dims[0] ?: 848);
        $outH = (int) ($dims[1] ?: 480);

        // ─── Cached small background — generated ONCE, reused after ───
        // Ye hi asli fix hai: 8000x4500 ko baar baar decode/scale karne
        // ki bajaye, ek chhoti copy banake reuse karo.
        $cacheDir = storage_path('app/public/cache');
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }
        $smallBg = $cacheDir . "/frame_bg_{$outW}x{$outH}.jpg";

        if (!file_exists($smallBg) || filemtime($smallBg) < filemtime($frameJpg)) {
            $src   = imagecreatefromjpeg($frameJpg);
            $small = imagecreatetruecolor($outW, $outH);
            imagecopyresampled($small, $src, 0, 0, 0, 0, $outW, $outH, self::MASTER_W, self::MASTER_H);
            imagejpeg($small, $smallBg, 90);
            imagedestroy($src);
            imagedestroy($small);
        }

        // Uploaded photo is a transparent circular layer. frame.png supplies the rim/design above it.
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
        $vh = (int) round(self::VIDEO_H * $scaleY);

        $tName     = ['x' => (int) round(self::TEXT_NAME['x'] * $scaleX),     'y' => (int) round(self::TEXT_NAME['y'] * $scaleY)];
        $tHospital = ['x' => (int) round(self::TEXT_HOSPITAL['x'] * $scaleX), 'y' => (int) round(self::TEXT_HOSPITAL['y'] * $scaleY)];
        $tMobile   = ['x' => (int) round(self::TEXT_MOBILE['x'] * $scaleX),   'y' => (int) round(self::TEXT_MOBILE['y'] * $scaleY)];
        $tDoctor   = ['x' => (int) round(self::TEXT_DOCTOR['x'] * $scaleX),   'y' => (int) round(self::TEXT_DOCTOR['y'] * $scaleY)];
        $labelX = (int) round(2700 * $scaleX);
        $lineEndX = (int) round(5600 * $scaleX);
        $lineOffset = max(2, (int) round(95 * $scaleY));

        // ─── Video Generate (FFmpeg) ────────────────────────────────────
        if (!is_dir(storage_path('app/public/videos'))) {
            mkdir(storage_path('app/public/videos'), 0777, true);
        }

        $videoName = 'video_' . time() . '.mp4';
        $videoPath = storage_path('app/public/videos/' . $videoName);

        exec(
            "ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 "
            . escapeshellarg($frameMp4),
            $probeOut
        );
        $mp4Duration   = (float) ($probeOut[0] ?? 5);
        $totalDuration = $mp4Duration;

        $fontEsc     = $this->ffmpegEscape($fontPath);
        $nameEsc     = $this->ffmpegEscape($doctor->name ?? '');
        $hospitalEsc = $this->ffmpegEscape($request->hospital_name);
        $mobileEsc   = $this->ffmpegEscape($request->mobile);
        $doctorLabelEsc = $this->ffmpegEscape('Dr. ' . ($doctor->name ?? ''));
        $nameLabelEsc = $this->ffmpegEscape('Name :');
        $hospitalLabelEsc = $this->ffmpegEscape('Hospital :');
        $mobileLabelEsc = $this->ffmpegEscape('Mobile :');

        // input 0 = frame.mp4, full moving background
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
            . "[framed]drawtext=fontfile='{$fontEsc}':text='{$doctorLabelEsc}':x={$tDoctor['x']}:y={$tDoctor['y']}:fontsize=25:fontcolor=black,"
            .   "drawtext=fontfile='{$fontEsc}':text='{$nameLabelEsc}':x={$labelX}:y={$tName['y']}:fontsize=20:fontcolor=black,"
            .   "drawtext=fontfile='{$fontEsc}':text='{$hospitalLabelEsc}':x={$labelX}:y={$tHospital['y']}:fontsize=20:fontcolor=black,"
            .   "drawtext=fontfile='{$fontEsc}':text='{$mobileLabelEsc}':x={$labelX}:y={$tMobile['y']}:fontsize=20:fontcolor=black,"
            .   "drawtext=fontfile='{$fontEsc}':text='{$nameEsc}':x={$tName['x']}:y={$tName['y']}:fontsize=25:fontcolor=black,"
            .   "drawtext=fontfile='{$fontEsc}':text='{$hospitalEsc}':x={$tHospital['x']}:y={$tHospital['y']}:fontsize=22:fontcolor=black,"
            .   "drawtext=fontfile='{$fontEsc}':text='{$mobileEsc}':x={$tMobile['x']}:y={$tMobile['y']}:fontsize=22:fontcolor=black,"
            .   "drawbox=x={$tName['x']}:y=" . ($tName['y'] + $lineOffset) . ":w=" . ($lineEndX - $tName['x']) . ":h=2:color=black:t=fill,"
            .   "drawbox=x={$tHospital['x']}:y=" . ($tHospital['y'] + $lineOffset) . ":w=" . ($lineEndX - $tHospital['x']) . ":h=2:color=black:t=fill,"
            .   "drawbox=x={$tMobile['x']}:y=" . ($tMobile['y'] + $lineOffset) . ":w=" . ($lineEndX - $tMobile['x']) . ":h=2:color=black:t=fill[outv]";

        $command = "ffmpeg -y "
            . "-i " . escapeshellarg($frameMp4) . " "
            . "-i " . escapeshellarg($circleOverlay) . " "
            . "-i " . escapeshellarg($framePng) . " "
            . "-filter_complex \"{$filter}\" "
            . "-map \"[outv]\" "
            . "-c:v libx264 -preset ultrafast -crf 25 -threads 0 "
            . "-pix_fmt yuv420p "
            . "-t {$totalDuration} "
            . escapeshellarg($videoPath);

        exec($command . " 2>&1", $ffmpegOutput, $returnCode);

        // ─── Update Doctor ────────────────────────────────────
        $doctor->update([
            'mobile'        => $request->mobile,
            'speciality'    => $request->speciality,
            'hospital_name' => $request->hospital_name,
            'address'       => $request->hospital_address,
            'profile_image' => $photoPath,
            'language'      => $request->language,
        ]);

        // ─── Save DB ──────────────────────────────────────────
        $userFile = UserFile::create([
            'user_id'     => $doctor->id,
            'photo'       => $photoPath,
            'banner_path' => 'banners/' . $bannerName,
            'video'       => 'videos/' . $videoName,
            'language'    => $request->language,
        ]);

        return redirect()->route('video.index')->with([
            'generated'    => true,
            'user_file_id' => $userFile->id,
        ]);
    }

    public function downloadBanner($id)
    {
        $file       = UserFile::findOrFail($id);
        $bannerPath = storage_path('app/public/' . $file->banner_path);
        return response()->download($bannerPath);
    }

    public function downloadVideo($id)
    {
        $file      = UserFile::findOrFail($id);
        $videoPath = storage_path('app/public/' . $file->video);
        return response()->download($videoPath);
    }

    // ── Helpers ─────────────────────────────────────────────────────

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

        imagepng($overlay, $outputPath, 6);
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

    private function resolveFontPath(): string
    {
        $candidates = array_filter([
            self::FONT_PATH ?: null,
            public_path('fonts/DejaVuSans-Bold.ttf'),
            public_path('fonts/Poppins-Bold.ttf'),
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
        ]);

        foreach ($candidates as $path) {
            if ($path && file_exists($path)) {
                return $path;
            }
        }

        // Koi TTF nahi mili — banner/video text ya to nahi dikhega ya
        // microscopic bitmap font me dikhega. Log me clearly likh do
        // taaki ye silently miss na ho.
        Log::warning('VideoController: no TTF font found — set FONT_PATH to a real .ttf file. Banner/video text will not render correctly.');

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
