<?php

namespace Modules\Media\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Modules\Media\app\Models\Media;
use Modules\Post\app\Models\Post;

/**
 * Factory class for generating Media models.
 */
class MediaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Media::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement([Media::IMAGE_TYPE, Media::VIDEO_TYPE]);
        $extension = $type === Media::IMAGE_TYPE
            ? $this->faker->randomElement(Config::get('media.supported_image_extensions', ['.png', '.jpg']))
            : $this->faker->randomElement(Config::get('media.supported_video_extensions', ['.mp4', '.mov']));

        $filePath = $type . '/' . Str::uuid()->toString() . '.' . $extension;

        return [
            'id' => Str::ulid()->toBase32(),
            'path' => $filePath,
            'thumbnail' => 'thumbnails/' . Str::uuid()->toString() . '.' . $this->faker->randomElement(Config::get('media.supported_image_file_formats', ['.png', '.jpg'])),
            'type' => $type,
            'post_id' => Post::inRandomOrder()->first() ?? Post::factory()->create(),
        ];
    }

    /**
     * Define the availability item state.
     *
     * @return MediaFactory
     */
    public function availabilityItem(): self
    {
        return $this->state(fn () => [
            'post_id' => null,
        ]);
    }

    /**
     * Define the image item state.
     *
     * @return MediaFactory
     */
    public function imageItem(): self
    {
        $extension = $this->faker->randomElement(Config::get('media.supported_image_extensions', ['.png', '.jpg']));
        $filePath = Media::IMAGE_TYPE . '/' . Str::uuid()->toString() . '.' . $extension;

        return $this->state(fn () => [
            'type' => Media::IMAGE_TYPE,
            'path' => $filePath,
        ]);
    }

    /**
     * Define the video item state.
     *
     * @return MediaFactory
     */
    public function videoItem(): self
    {
        $extension = $this->faker->randomElement(Config::get('media.supported_video_extensions', ['.mp4', '.mov']));
        $filePath = Media::VIDEO_TYPE . '/' . Str::uuid()->toString() . '.' . $extension;

        return $this->state(fn () => [
            'type' => Media::VIDEO_TYPE,
            'path' => $filePath,
            'thumbnail' => 'thumbnails/' . Str::uuid()->toString() . '.' . $this->faker->randomElement(Config::get('media.supported_image_file_formats', ['.png', '.jpg'])),
        ]);
    }
}
