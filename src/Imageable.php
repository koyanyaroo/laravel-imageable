<?php

namespace Koyanyaroo;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

/**
 * Trait Imageable
 *
 * @package Koyanyaroo
 */
trait Imageable
{
    /**
     * Get the image file url if exist
     *
     * @param null $attribute
     * @return string relation
     */
    public function getImage($attribute)
    {
        if (! $this->hasImage($attribute)) {
            return null;
        }

        return Storage::disk('public')->url($this->uploadDir.'/'.$this->{$attribute});
    }

    /**
     * Get the image file path if exist
     *
     * @param null $attribute
     * @return string relation
     */
    public function getImagePath($attribute)
    {
        if (! $this->hasImage($attribute)) {
            return null;
        }

        return Storage::disk('public')->path($this->uploadDir.'/'.$this->{$attribute});
    }

    /**
     * Get the image thumbnail url if exist
     *
     * @param null $attribute
     * @return string relation
     */
    public function getThumbnail($attribute)
    {
        if (! $this->hasThumbnail($attribute)) {
            return null;
        }

        return Storage::disk('public')->url($this->uploadDir.'/thumb_'.$this->{$attribute});
    }

    /**
     * Check whether image file exist by the given attribute name
     *
     * @param $attribute
     * @return mixed
     */
    public function hasImage($attribute)
    {
        return Storage::disk('public')->has($this->uploadDir.'/'.$this->{$attribute}) && $this->{$attribute};
    }

    /**
     * Check whether image thumbnail exist by the given attribute name
     *
     * @param $attribute
     * @return mixed
     */
    public function hasThumbnail($attribute)
    {
        return Storage::disk('public')->has($this->uploadDir.'/thumb_'.$this->{$attribute}) && $this->{$attribute};
    }

    /**
     * Delete image file if exist by the given attribute name
     * it will delete the thumbnail image if exist
     *
     * @param $attribute
     * @return bool|void
     */
    public function deleteImage($attribute)
    {
        if (! $this->hasImage($attribute)) {
            return true;
        }

        $this->deleteThumbnail($attribute);

        return Storage::disk('public')->delete($this->uploadDir.'/'.$this->{$attribute});
    }

    /**
     * Delete image thumbnail if exist by the given attribute name
     *
     * @param $attribute
     * @return bool|void
     */
    public function deleteThumbnail($attribute)
    {
        if (! $this->hasThumbnail($attribute)) {
            return true;
        }

        return Storage::disk('public')->delete($this->uploadDir.'/thumb_'.$this->{$attribute});
    }

    /**
     * Upload the image according to the form name given
     *
     * @param Request $request
     * @param string $formName
     * @return string|null
     */
    public function uploadImage(Request $request, $formName = 'image')
    {
        if (! $request->hasFile($formName)) {
            return null;
        }

        $file = $request->file($formName);
        $filename = preg_replace('/\..+$/', '', $file->getClientOriginalName());
        $fileUpload = Str::slug($filename, '-') . '-' . Str::random(5) . '.' . $file->getClientOriginalExtension();

        Storage::disk('public')->put($this->uploadDir. '/' . $fileUpload, file_get_contents($file), 'public');

        return $fileUpload;
    }

    /**
     * Upload the image automatically if defined on model
     *
     * @param UploadedFile|string $uploadedFile
     * @return string|null
     */
    protected function uploadImageBoot($uploadedFile)
    {
        if (!$uploadedFile instanceof UploadedFile) {
            return $uploadedFile;
        }

        $filename = preg_replace('/\..+$/', '', $uploadedFile->getClientOriginalName());
        $fileUpload = Str::slug($filename, '-') . '-' . Str::random(5) . '.' . $uploadedFile->getClientOriginalExtension();

        Storage::disk('public')->put($this->uploadDir. '/' . $fileUpload, file_get_contents($uploadedFile), 'public');

        return $fileUpload;
    }

    /**
     * Delete image thumbnail if exist by the given filename
     *
     * @param $filename
     * @return bool|void
     */
    protected function deleteThumbnailByFilename($filename)
    {
        if (! $this->hasThumbnailByFilename($filename)) {
            return true;
        }

        return Storage::disk('public')->delete($this->uploadDir.'/thumb_'.$filename);
    }

    /**
     * Check whether image thumbnail exist by the given filename
     *
     * @param $filename
     * @return mixed
     */
    protected function hasThumbnailByFilename($filename)
    {
        return Storage::disk('public')->has($this->uploadDir.'/thumb_'.$filename);
    }

    /**
     * Check whether image file exist by the given filename
     *
     * @param $filename
     * @return mixed
     */
    public function hasImageByFilename($filename)
    {
        return Storage::disk('public')->has($this->uploadDir.'/'.$filename);
    }

    /**
     * Get the image file path if exist
     *
     * @param $filename
     * @return string relation
     */
    public function getImagePathByFilename($filename)
    {
        if (! $this->hasImageByFilename($filename)) {
            return null;
        }

        return Storage::disk('public')->path($this->uploadDir.'/'.$filename);
    }

    /**
     * Delete image file if exist by the given filename
     * it will delete the thumbnail image if exist
     *
     * @param $filename
     * @return bool|void
     */
    protected function deleteImageByFilename($filename)
    {
        if (! $this->hasImageByFilename($filename)) {
            return true;
        }

        $this->deleteThumbnailByFilename($filename);

        return Storage::disk('public')->delete($this->uploadDir.'/'.$filename);
    }

    /**
     *
     * @param $attribute
     * @param int $width
     * @param int $height
     * @return bool
     */
    public function generateThumbnail($attribute, $width = 150, $height = 150)
    {
        if (! $this->hasImage($attribute)) {
            return false;
        }

        $thumb = Image::make($this->getImagePath($attribute));

        $thumb->resize($width, $height, function ($constraint) {
            $constraint->aspectRatio();
        });

        $thumb->stream();

        return Storage::disk('public')->put($this->uploadDir. '/thumb_' . $this->{$attribute}, $thumb, 'public');
    }

    /**
     * This boot will only applied to model that use Imageable Trait
     * it will automatically upload when $imageableField defined on their model
     *
     * creating: will upload the uploaded image before create()
     * updating: will delete the old image, then upload the new one before update()
     * deleted: will delete image after when the model about to delete()
     *
     * @todo - let the model define the path of upload directory (currently use module property on model)
     *       - please refactor the module name
     * @see $imageableField
     */
    public static function bootImageable()
    {
        // this will uploading image automatically
        static::creating(function($item){
            collect($item->imageableField)->each(function ($field, $key) use ($item) {
                $key = is_string($key) ? $key : $field;
                $item->{$key} = $item->uploadImageBoot($item->{$key});
            });

            collect($item->imageableField)->filter(function ($field) {
                return isset($field['thumb']);
            })->each(function ($field, $key) use ($item) {
                $item->generateThumbnail($key, $field['thumb'][0], $field['thumb'][1]);
            });
        });

        static::updating(function($item){
            collect($item->imageableField)->each(function ($field, $key) use ($item) {
                $key = is_string($key) ? $key : $field;
                $item->{$key} = $item->uploadImageBoot($item->{$key});
                if ($item->getOriginal($key) != $item->{$key} && !empty($item->getOriginal($key))) {
                    $item->deleteImageByFilename($item->getOriginal($key));
                }
            });

            collect($item->imageableField)->filter(function ($field) {
                return isset($field['thumb']);
            })->each(function ($field, $key) use ($item) {
                $item->generateThumbnail($key, $field['thumb'][0], $field['thumb'][1]);
            });
        });

        static::deleted(function($item){
            collect($item->imageableField)->each(function ($field, $key) use ($item) {
                $key = is_string($key) ? $key : $field;
                $item->deleteImage($key);
            });
        });
    }
}
