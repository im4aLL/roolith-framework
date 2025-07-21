<?php
namespace App\Core;

use App\Models\Model;
use App\Utils\_;

class LazyLoad
{
    private iterable $data = [];
    private iterable $result = [];
    private array $loadArray;
    private bool $isAddArrayResult = false;
    private array $settings = [];

    public function __construct(iterable $data, $settings = [])
    {
        $this->data = $data;
        $this->result = $data;

        if (isset($settings['add_array'])) {
            $this->isAddArrayResult = $settings['add_array'];
        }
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
        $this->loadArray[] = _::arrayToObject([
            'model' => $model,
            'foreignKey' => $foreignKey,
            'localKey' => $localKey,
        ]);

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
     * @param object $dto
     * @return void
     */
    private function attachModelData(object $dto): void
    {
        $array = explode('\\', $dto->model);
        $key = _::pascalCaseToSnakeCase(end($array));
        $ids = [];

        foreach ($this->result as $item) {
            $item->{$key} = null;
            if ($this->isAddArrayResult) {
                $item->{$key . '_array'} = null;
            }


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
            $modelData = _::filter($data, function ($modelDataItem) use ($dto, $item) {
                return $modelDataItem->{$dto->localKey} === $item->{$dto->foreignKey};
            });

            if (!$modelData) {
                continue;
            }

            $item->{$key} = count($modelData) == 1 ? _::first($modelData) : $modelData;
            if ($this->isAddArrayResult) {
                $item->{$key . '_array'} = $modelData;
            }
        }
    }
}
