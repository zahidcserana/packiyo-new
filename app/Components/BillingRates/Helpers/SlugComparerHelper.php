<?php

namespace App\Components\BillingRates\Helpers;

use App\Interfaces\SoftDeletableSluggable;
use App\Models\ShippingMethod;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class SlugComparerHelper
{
    /**
     * @var array<string, array<string|int, string>>
     * Example:
     *   [
     *     'App\Models\ShippingMethod' => [
     *         1 => 'slug',
     *         2 => 'slug-1'
     *     ]
     *   ]
     */
    private static array $cacheInformation = [];

    public static function compareUsingModels(SoftDeletableSluggable $firstModel, SoftDeletableSluggable $secondModel): bool
    {
        if (get_class($firstModel) !== get_class($secondModel)) {
            throw new InvalidArgumentException('Models must be of the same class');
        }

        Log::debug(
            sprintf('[SlugCompare] Compare class %s ids: [%s, %s]', get_class($firstModel), $firstModel->getKey(), $secondModel->getKey())
        );

        if ($firstModel->getKey() === $secondModel->getKey()) {
            return true;
        }

        $firstModelSlug = self::getSlugByModel($firstModel);
        $secondModelSlug = self::getSlugByModel($secondModel);

        return $firstModelSlug === $secondModelSlug;
    }

    public static function compareByClass(string $className, int $firstIdentity, int $secondIdentity): bool
    {
        if (!is_subclass_of($className, SoftDeletableSluggable::class)) {
            throw new InvalidArgumentException('Class must implement DeletableSluggable interface');
        }

        if ($firstIdentity === $secondIdentity) {
            return true;
        }

        $firstModelSlug = self::getSlugByClass($className, $firstIdentity);
        $secondModelSlug = self::getSlugByClass($className, $secondIdentity);

        return $firstModelSlug === $secondModelSlug;
    }

    private static function getSlugByModel(SoftDeletableSluggable $model): string
    {
        $modelSlug = self::getFromCache(get_class($model), $model->getKey());
        if ($modelSlug === null) {
            $modelSlug = $model->slugify();
            self::cache(get_class($model), $model->getKey(), $modelSlug);
        }
        return $modelSlug;
    }

    private static function getSlugByClass(string $className, int $identity): string
    {
        // Here we are sure that the class implements the SoftDeletableSluggable interface, since we're calling this after
        // the check in the compareByClass method
        $modelSlug = self::getFromCache($className, $identity);

        if ($modelSlug === null) {
            $model = $className::query()->withTrashed()->find($identity);
            $modelSlug = $model->slugify();
            self::cache($className, $identity, $modelSlug);
        }

        return $modelSlug;
    }

    public static function elementExistInCached(string $className, int $id): bool
    {
        return isset(self::$cacheInformation[$className][$id]);
    }

    private static function getFromCache(string $className, int $identity): string | null
    {
        return self::$cacheInformation[$className][$identity] ?? null;
    }

    private static function cache(string $className, int $identity, string $slug): void
    {
        self::$cacheInformation[$className][$identity] = $slug;
    }
}
