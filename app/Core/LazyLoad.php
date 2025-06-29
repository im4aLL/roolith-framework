<?php
namespace App\Core;

use App\Core\Dto\LazyLoadDTO;
use App\Models\Model;
use App\Utils\_;

class LazyLoad
{
    private iterable $data = [];
    private iterable $result = [];
    private array $loadArray;

    public function __construct(iterable $data)
    {
        $this->data = $data;
        $this->result = $data;
    }

    /**
     * Add items load array
     *
     * @param string $model
     * @param string $foreignKey
     * @param string $localKey
     * @return $this
     */
    public function with(string $model, string $foreignKey, string $localKey = 'id'): static
    {
        $this->loadArray[] = LazyLoadDTO::create($model, $foreignKey, $localKey);

        return $this;
    }

    /**
     * Attach data and returns new result
     *
     * @return iterable
     */
    public function get(): iterable
    {
        foreach ($this->loadArray as $item) {
            $this->attachModelData($item);
        }

        return $this->result;
    }

    /**
     * Load additional data and inject into result
     *
     * @param LazyLoadDTO $dto
     * @return void
     */
    private function attachModelData(LazyLoadDTO $dto): void
    {
        $key = $dto->foreignKey . '_data';
        $ids = [];

        foreach ($this->result as $item) {
            $item->{$key} = null;

            if ($item->{$dto->foreignKey}) {
                $ids[] = $item->{$dto->foreignKey};
            }
        }

        $uniqueIds = _::uniq($ids);
        if (count($uniqueIds) == 0) {
            return;
        }

        $modelInstance = (fn($instance):Model => $instance)(new ($dto->model)());
        $data = $modelInstance::orm()->select([
            'condition' => 'WHERE '.$dto->localKey.' IN ('.implode(',', $uniqueIds).')'
        ])->get();

        if (!$data) {
            return;
        }

        foreach ($this->result as $item) {
            $modelData = _::find($data, function ($modelDataItem) use ($dto, $item) {
                return $modelDataItem->{$dto->localKey} === $item->{$dto->foreignKey};
            });

            if ($modelData) {
                $item->{$key} = $modelData;
            }
        }
    }
}
