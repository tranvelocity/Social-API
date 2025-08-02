<?php

namespace Modules\Media\app\Http\Requests;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Core\app\Constants\StatusCodeConstant;
use Modules\Core\app\Exceptions\ValidationErrorException;
use Modules\Core\app\Http\Requests\JsonRequest;

class CreateMediaRequest extends JsonRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                function ($attribute, $file) {
                    $this->validateFileType($file);
                },
            ],
            'thumbnail' => [
                'nullable',
                'file',
                function ($attribute, $thumbnail) {
                    $this->validateThumbnailFileType($thumbnail);
                },
            ],
        ];
    }

    /**
     * Validate the thumbnail file type.
     *
     * @param UploadedFile $thumbnail
     * @return void
     * @throws ValidationException
     */
    private function validateThumbnailFileType(UploadedFile $thumbnail)
    {
        $rules = [
            'image',
            'mimes:' . Rule::in(config('media.supported_image_extensions')),
            'max:' . config('media.image_upload_size_limit'),
        ];

        $this->validateFile($thumbnail, $rules);
    }

    /**
     * Validate the file type (image or video).
     *
     * @param UploadedFile $file
     * @return void
     * @throws ValidationException
     */
    private function validateFileType(UploadedFile $file)
    {
        $imageRules = [
            'image',
            'mimes:' . Rule::in(config('media.supported_image_extensions')),
            'max:' . config('media.image_upload_size_limit'),
        ];

        $videoRules = [
            'video',
            'mimes:' . implode(',', config('media.supported_video_extensions')),
            'max:' . config('media.video_upload_size_limit'),
        ];

        if (str_starts_with($file->getMimeType(), 'image')) {
            $this->validateFile($file, $imageRules);
        } elseif (str_starts_with($file->getMimeType(), 'video')) {
            $this->validateFile($file, $videoRules);
        } else {
            $this->throwInvalidFileTypeException();
        }
    }

    /**
     * Validate a file against the provided rules.
     *
     * @param UploadedFile $file
     * @param array $rules
     * @return void
     * @throws ValidationException
     */
    private function validateFile(UploadedFile $file, array $rules)
    {
        $validator = Validator::make(['file' => $file], ['file' => $rules]);

        if ($validator->fails()) {
            $this->failedValidation($validator);
        }
    }

    /**
     * Handle validation failure with a custom message.
     *
     * @return void
     */
    private function throwInvalidFileTypeException()
    {
        throw new ValidationErrorException(StatusCodeConstant::BAD_REQUEST_VALIDATION_FAILED_CODE, 'Invalid file type. Supported types: image, video');
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
