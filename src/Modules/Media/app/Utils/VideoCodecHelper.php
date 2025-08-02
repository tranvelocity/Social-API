<?php

namespace Modules\Media\app\Utils;

use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Class VideoCodecHelper.
 *
 * Utility class for video codec operations, including codec detection and conversion.
 *
 * - Uses FFprobe to detect the codec of a video file.
 * - Uses FFmpeg to convert HEVC (H.265) videos to H.264 (MP4).
 * - Designed for use in media upload and processing pipelines.
 */
class VideoCodecHelper
{
    /**
     * Detect the codec of a video file using ffprobe.
     *
     * This method runs the ffprobe command-line tool to analyze the provided video file
     * and extract the codec name of the first video stream.
     *
     * @param string $filePath Absolute path to the video file to analyze.
     *
     * @return string|null The codec name (e.g., 'h264', 'hevc', 'vp9'), or null if detection fails.
     *
     * @throws RuntimeException If ffprobe is not available or the command fails.
     */
    public static function getVideoCodec(string $filePath): ?string
    {
        $cmd = 'ffprobe -v error -select_streams v:0 -show_entries stream=codec_name -of default=noprint_wrappers=1:nokey=1 ' . escapeshellarg($filePath);
        Log::info('[FFprobe] Running command: ' . $cmd);
        $output = [];
        $returnVar = 0;
        exec($cmd, $output, $returnVar);
        Log::info('[FFprobe] Output: ' . implode("\n", $output));
        Log::info('[FFprobe] Return code: ' . $returnVar);
        $codec = trim(implode("\n", $output));

        return $codec ?: null;
    }

    /**
     * Convert a video file to H.264 using ffmpeg.
     *
     * This method uses the ffmpeg command-line tool to convert the input video file to H.264 (MP4) format.
     * The audio stream is copied without re-encoding.
     *
     * @param string $inputPath Absolute path to the input video file.
     *
     * @return string Absolute path to the converted H.264 MP4 file.
     *
     * @throws RuntimeException If ffmpeg conversion fails.
     */
    public static function convertToH264(string $inputPath): string
    {
        $outputPath = tempnam(sys_get_temp_dir(), 'h264_') . '.mp4';
        $cmd = 'ffmpeg -y -i ' . escapeshellarg($inputPath) . ' -c:v libx264 -preset fast -crf 23 -c:a copy ' . escapeshellarg($outputPath) . ' 2>&1';
        $output = [];
        $returnVar = 0;
        exec($cmd, $output, $returnVar);
        if ($returnVar !== 0) {
            throw new RuntimeException('FFmpeg conversion failed: ' . implode("\n", $output));
        }

        return $outputPath;
    }
}
