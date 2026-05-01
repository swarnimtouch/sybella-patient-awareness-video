<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserFile;

class VideoController extends Controller
{
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
            'language'         => 'required',
            'photo'            => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $doctor = User::where('id', $request->doctor_id)
            ->where('parent_id', auth()->id())
            ->firstOrFail();

        // 📸 Upload photo
        $photoPath     = $request->file('photo')->store('uploads', 'public');
        $photoFullPath = storage_path('app/public/' . $photoPath);

        $frameMp4 = public_path('video/frame.mp4');
        $framePng = public_path('video/frame.png');

        // ─── Banner Generate (GD) ─────────────────────────────
        if (!file_exists(storage_path('app/public/banners'))) {
            mkdir(storage_path('app/public/banners'), 0777, true);
        }

        $bannerName = 'banner_' . time() . '.png';
        $bannerPath = storage_path('app/public/banners/' . $bannerName);

        // Banner coordinates (frame.png 1672x941)
        $bx = 675; $by = 88; $bw = 625; $bh = 435;

        $frame = imagecreatefrompng($framePng);
        $ext   = strtolower(pathinfo($photoFullPath, PATHINFO_EXTENSION));
        $photo = ($ext === 'png')
            ? imagecreatefrompng($photoFullPath)
            : imagecreatefromjpeg($photoFullPath);

        $resized = imagecreatetruecolor($bw, $bh);
        imagecopyresampled($resized, $photo, 0, 0, 0, 0, $bw, $bh, imagesx($photo), imagesy($photo));
        imagecopy($frame, $resized, $bx, $by, 0, 0, $bw, $bh);
        imagepng($frame, $bannerPath);
        imagedestroy($frame);
        imagedestroy($photo);
        imagedestroy($resized);

        // ─── Video Generate (FFmpeg) ──────────────────────────
        if (!file_exists(storage_path('app/public/videos'))) {
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
        $photoDuration = 5;
        $totalDuration = $mp4Duration + $photoDuration;

        // Video coordinates (frame.mp4 848x480 — scaled from 1672x941)
        $scaleX = 848 / 1672;
        $scaleY = 480 / 941;
        $vx = (int) round(675 * $scaleX);
        $vy = (int) round(88  * $scaleY);
        $vw = (int) round(625 * $scaleX);
        $vh = (int) round(435 * $scaleY);

        $command = "ffmpeg -y "
            . "-loop 1 -i " . escapeshellarg($framePng) . " "
            . "-i " . escapeshellarg($frameMp4) . " "
            . "-loop 1 -i " . escapeshellarg($photoFullPath) . " "
            . "-filter_complex \""
            .   "[1:v]scale={$vw}:{$vh},setsar=1[innervid];"
            .   "[2:v]scale={$vw}:{$vh},setsar=1[innerphoto];"
            .   "[innervid]trim=0:{$mp4Duration},setpts=PTS-STARTPTS[v1];"
            .   "[innerphoto]trim=0:{$photoDuration},setpts=PTS-STARTPTS[v2];"
            .   "[v1][v2]concat=n=2:v=1:a=0[innerout];"
            .   "[0:v]scale=trunc(iw/2)*2:trunc(ih/2)*2,setsar=1[bg];"
            .   "[bg][innerout]overlay={$vx}:{$vy}"
            . "\" "
            . "-c:v libx264 -preset ultrafast "
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
        $file      = UserFile::findOrFail($id);
        $bannerPath = storage_path('app/public/' . $file->banner_path);
        return response()->download($bannerPath);
    }

    public function downloadVideo($id)
    {
        $file      = UserFile::findOrFail($id);
        $videoPath = storage_path('app/public/' . $file->video);
        return response()->download($videoPath);
    }
}
