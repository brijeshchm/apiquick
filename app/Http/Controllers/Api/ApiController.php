<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Mostafaznv\PdfOptimizer\Pdf;

use Mostafaznv\PdfOptimizer\Laravel\Facade\PdfOptimizer;
use Mostafaznv\PdfOptimizer\Enums\PdfSettings;
use Mostafaznv\PdfOptimizer\Enums\ColorConversionStrategy;
use Illuminate\Support\Facades\Storage;


/**
 * @OA\Info(
 *     title="Client API",
 *     version="1.0.0",
 *     description="API documentation for QuickDials application"
 * )
 * @OA\Tag(
 *     name="Users",
 *     description="Operations related to users"
 * )
 */
class ApiController extends Controller
{

    /**
     * Upload product documents from a zip file.
     *
     * @OA\Post(
     *     path="/api/upload-documents",
     *     summary="Upload product documents from zip file",
     *     description="Upload a ZIP file containing product documents organized by SKU folders, extract and process them to S3, and update product records in the database.",
     *     tags={"Products"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="zip_file",
     *                     type="string",
     *                     format="binary",
     *                     description="ZIP file containing product documents organized in folders by SKU"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Documents processed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product documents processed successfully"),
     *             @OA\Property(
     *                 property="processed_skus",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="sku", type="string", example="ABC123"),
     *                     @OA\Property(property="status", type="string", example="success", description="success, no_documents_found, or product_not_found"),
     *                     @OA\Property(property="document_count", type="integer", example=5, description="Number of documents processed for this SKU")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid input",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Unable to open the zip file")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="zip_file",
     *                     type="array",
     *                     @OA\Items(type="string", example="The zip file field is required.")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="string", example="Error message")
     *         )
     *     )
     * )
     */

    public function uploadPdf(Request $request)
    {
        $request->validate([
            'zip_file' => 'required|file|mimes:pdf|max:10240', // Max 10MB
        ]);
        // Store the uploaded file
        $file = $request->file('zip_file');
        $filename = $file->getClientOriginalName();
        $disk = 'public'; // your disk in config/filesystems.php
        $pdf = $request->file('zip_file');
        $path = $pdf->store('pdfs', 'public');
        $filePath = storage_path("app/public/{$path}");
        if (!file_exists($filePath)) {
            mkdir($filePath, 0777, true);
        }


        $gsPath = 'C:\Program Files\gs\gs10.05.1\bin\gswin64c.exe'; // adjust if different
        $compressedPath = realpath(storage_path("app/public/pdfs")) . DIRECTORY_SEPARATOR . "compressed_" . $pdf->getClientOriginalName();
        //  dd($compressedPath);


        if (!file_exists($compressedPath)) {
            mkdir($compressedPath, 0777, true);
        }
        $temp = storage_path('app/tmp');
        if (!file_exists($temp)) {
            mkdir($temp, 0777, true);
        }   // Compress the PDF
        try {
            $result = PdfOptimizer::fromDisk('local') // Source disk
                ->open($filePath) // Input file path
                ->toDisk('public') // Output to public disk
                ->settings(PdfSettings::SCREEN) // Low quality for web
                ->colorConversionStrategy(ColorConversionStrategy::DEVICE_INDEPENDENT_COLOR)
                ->colorImageResolution(72) // Adjust resolution (1-500)
                ->optimize($compressedPath); // Output file path

            if ($result->status === 0) {
                // Success: Return the download link
                $fileUrl = asset('storage/' . $filePath);
                return response()->json([
                    'message' => 'PDF compressed successfully!',
                    'file_url' => $fileUrl,
                ]);
            } else {
                // Error: Return the error message
                return response()->json(['error' => $result->message], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Compression failed: ' . $e->getMessage()], 500);
        }

    }
    public function uploadPdf_opdd(Request $request)
    {
        // Validate the uploaded PDF
        $request->validate([
            'zip_file' => 'required|file|mimes:pdf|max:10240', // Max 10MB
        ]);

        // Store the uploaded file
        $uploadedFile = $request->file('zip_file');
        $inputPath = $uploadedFile->store('uploads', 'local'); // Store in storage/app/uploads

        // Define output path
        $outputPath = 'optimized/' . time() . '_compressed.pdf';

        // Compress the PDF
        try {
            $result = PdfOptimizer::fromDisk('local') // Source disk
                ->open($inputPath) // Input file path
                ->toDisk('public') // Output to public disk
                ->settings(PdfSettings::SCREEN) // Low quality for web
                ->colorConversionStrategy(ColorConversionStrategy::DEVICE_INDEPENDENT_COLOR)
                ->colorImageResolution(72) // Adjust resolution (1-500)
                ->optimize($outputPath); // Output file path

            if ($result->status === 0) {
                // Success: Return the download link
                $fileUrl = asset('storage/' . $outputPath);
                return response()->json([
                    'message' => 'PDF compressed successfully!',
                    'file_url' => $fileUrl,
                ]);
            } else {
                // Error: Return the error message
                return response()->json(['error' => $result->message], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Compression failed: ' . $e->getMessage()], 500);
        }
    }


    public function uploadPdf_llkk0(Request $request)
    {
        $request->validate([
            'zip_file' => 'required|file|mimes:pdf|max:102400',
        ]);

        $file = $request->file('zip_file');
        $filename = $file->getClientOriginalName();
        $disk = 'public'; // your disk in config/filesystems.php
        $pdf = $request->file('zip_file');
        $path = $pdf->store('pdfs', 'public');
        $filePath = storage_path("app/public/{$path}");
        if (!file_exists($filePath)) {
            mkdir($filePath, 0777, true);
        }


        $gsPath = 'C:\Program Files\gs\gs10.05.1\bin\gswin64c.exe'; // adjust if different
        $compressedPath = realpath(storage_path("app/public/pdfs")) . DIRECTORY_SEPARATOR . "compressed_" . $pdf->getClientOriginalName();
        // dd($filePath);
        if (!file_exists($compressedPath)) {
            mkdir($compressedPath, 0777, true);
        }
        $temp = storage_path('app/tmp');
        if (!file_exists($temp)) {
            mkdir($temp, 0777, true);
        }
        $result = PdfOptimizer::fromDisk('local')
            ->open($filePath)                                   // storage/app/input.pdf
            ->setGsBinary($gsPath)                                // <-- Windows fix
            ->toDisk($disk)                                        // or 'public' during testing
            // ->toDisk('s3')         
            //  ->setTemporaryDirectory($temp)                               // or 'public' during testing
            ->settings(PdfSettings::SCREEN)                       // smallest size preset
            ->colorConversionStrategy(ColorConversionStrategy::DEVICE_INDEPENDENT_COLOR)
            ->colorImageResolution(50)                            // downsample color images
            ->optimize('output.pdf');


        // $result = PdfOptimizer::fromDisk($disk)
        //     ->open("pdfs/{$filename}")
        //     ->toDisk($disk)
        //     ->settings(PdfSettings::SCREEN)
        //     ->colorConversionStrategy(ColorConversionStrategy::DEVICE_INDEPENDENT_COLOR)
        //     ->colorImageResolution(50)
        //     ->optimize("pdfs/compressed_{$filename}");

        return response()->json([
            'status' => $result->status,
            'message' => $result->message,
        ]);
    }
    public function uploadpdf_old(Request $request)
    {

        $request->validate([
            'zip_file' => 'required|file|mimes:pdf|max:102400', // 100MB max size
        ]);

        $pdf = $request->file('zip_file');
        $path = $pdf->store('pdfs', 'public');
        $filePath = storage_path("app/public/{$path}");
        if (!file_exists($filePath)) {
            mkdir($filePath, 0777, true);
        }
        $fileSize = filesize($filePath);
        try {
            // If file size > 2MB, compress using Ghostscript
            if ($fileSize > 2 * 1024 * 1024) {
                // dd('fffffff');


                $compressedPath = realpath(storage_path("app/public/pdfs")) . DIRECTORY_SEPARATOR . "compressed_" . $pdf->getClientOriginalName();
                $filePath = realpath($filePath);

                $gsPath = '"C:\\Program Files\\gs\\gs10.05.1\\bin\\gswin64c.exe"';

                // $outputFile = '"' . $compressedPath . '"';
                // $inputFile  = '"' . $filePath . '"';
                $inputFile = storage_path('app/public/pdfs/Technical-Specifications-Sheet.pdf');
                $outputFile = storage_path('app/public/pdfs/compressed_Technical-Specifications-Sheet.pdf');


                //     $compressedPath = storage_path("app/public/pdfs/compressed_" . $pdf->getClientOriginalName());
                //     if (!file_exists($compressedPath)) {
                //         mkdir($compressedPath, 0777, true);
                //     }

                //     $outputFile = '"' . addslashes($compressedPath) . '"';
                // $inputFile  = '"' . addslashes($filePath) . '"';


                $gsPath = '"C:\\Program Files\\gs\\gs10.05.1\\bin\\gswin64c.exe"';
                // Ghostscript command
                $cmd = $gsPath . " -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/ebook"
                    . "-dNOPAUSE -dQUIET -dBATCH -sOutputFile=" . escapeshellarg($outputFile)
                    . " " . escapeshellarg($inputFile);

                exec($cmd . " 2>&1", $output, $returnVar);
                //exec($cmd, $output, $returnVar);
// dd($compressedPath);
                if ($returnVar === 0 && file_exists($compressedPath)) {
                    rename($compressedPath, $filePath);
                } else {
                    throw new \Exception("Ghostscript failed. Return: $returnVar, Output: " . implode("\n", $output));
                }
            }
        } catch (Exception $e) {
            echo "<pre>";
            print_r($e->getMessage());
        }

        return response()->json([
            'message' => 'PDF uploaded successfully ff!',
            'file_url' => asset("storage/{$path}")
        ]);

    }
}
