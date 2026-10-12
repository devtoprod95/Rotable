<?php

namespace App\Dtos;

use ReflectionClass;
use ReflectionProperty;

abstract class Dto
{
    /**
     * 요청 데이터를 DTO 프로퍼티에 매핑한다.
     * 정의된 프로퍼티만, null이 아닌 값일 때만 덮어써서 각 프로퍼티의 기본값을 보존한다.
     * 커스텀 변환이 필요한 DTO는 이 메서드를 오버라이드하면 된다.
     *
     * @param mixed $data
     * @return void
     */
    public function bind(mixed $data): void
    {
        if (!is_array($data)) {
            return;
        }

        foreach ($data as $key => $value) {
            if (property_exists($this, $key) && !is_null($value)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return json_decode(json_encode($this), true);
    }

    /**
     * @param mixed $list
     * @param string $class
     * @return Vo[]|array
     */
    protected function bindList(mixed $list, string $class): array
    {
        if (is_null($list)) {
            return [];
        }

        return array_map(function ($item) use ($class) {
            /** @var Vo $class */
            $object = new $class;
            $object->bind($item);

            return $object;
        }, $list);
    }

    public function __get($name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
    }

    public function __set($name, $value)
    {
        if (property_exists($this, $name)) {
            $this->$name = $value;
        }
    }

    public function getAllProperties(): array
    {
        $array = [];
        $reflection = new ReflectionClass($this);
        foreach ($reflection->getProperties(ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PUBLIC) as $property) {
            $property->setAccessible(true);
            $array[$property->getName()] = $property->getValue($this);
        }
        return $array;
    }
}
