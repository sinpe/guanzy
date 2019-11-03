<?php
/*
 * This file is part of the long/guanzy package.
 *
 * (c) Sinpe <support@sinpe.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sinpe\Framework\Http;

use Psr\Http\Message\ServerRequestInterface;
use Sinpe\Framework\ArrayObject;

/**
 * 请求检查
 * 
 * 检查器的使命是根据Request的输入，返回出可以逻辑层可以直接使用的数据对象
 * 
 * @author sinpe <18222544@qq.com>
 */
abstract class RequestInspector
{
    /**
     * 路由参数
     *
     * @var array
     */
    private $routeParams = [];

    /**
     * 子模式
     *
     * @var Object|string
     */
    protected $mode;

    /**
     * 严格
     *
     * @var boolean
     */
    private $strict = false;

    /**
     * 字段及检查顺序，可由子类覆盖
     *
     * @var array
     */
    protected $fields = [];

    /**
     * 字段的预设值
     *
     * @var array
     */
    protected $defaultValues = [];

    /**
     * 表单字段到表字段映射，由子类实际指定
     *
     * @var array
     */
    protected $tableField2FormFields = [
        // 'table_field_name' => 'form_field_name'
    ];

    /**
     * @var callable[]
     */
    private $finalizes = [];

    /**
     * 关联检查
     *
     * @return void
     */
    final public function finalize(callable $fn)
    {
        $this->finalizes[] = $fn;
        return $this;
    }

    /**
     * 设置模式
     *
     * @param Object|string $mode
     * @return static
     */
    final public function setMode($mode)
    {
        $this->mode = $mode;
        return $this;
    }

    /**
     * 设置字段
     *
     * @param array $fields 字段
     * @param array $defaultValues 预设值
     * 
     * @return void
     */
    final public function setFields(
        array $fields,
        array $defaultValues = []
    ) {
        $this->fields = $fields;
        $this->defaultValues = $defaultValues;
        return $this;
    }

    /**
     * 表字段映射到表单字段
     *
     * @param string $tableField
     * @param bool $inverse 逆向
     * @return string
     */
    final public function tableField2FormField(string $field, $inverse = false): string
    {
        if ($inverse) {
            $tableField = array_search($field, $this->tableField2FormFields, true);
            if ($tableField !== false) {
                return $tableField;
            }
        } else {
            if (isset($this->tableField2FormFields[$field])) {
                return $this->tableField2FormFields[$field];
            }
        }

        return $field;
    }

    /**
     * 设置严格模式开启，控制字段的检查范围，比如在增加、修改操作时过滤未指定的字段的输入
     *
     * @param boolean $value
     * @return static
     */
    public function setStrict(bool $value)
    {
        $this->strict = $value;
        return $this;
    }

    /**
     * 执行检查
     *
     * @param ServerRequestInterface $request
     * @return ArrayObject
     */
    final public function handle(ServerRequestInterface $request, ArrayObject $routeParams = null): ArrayObject
    {
        // 指定特定模式做检查
        if ($this->mode) {
            if (is_string($this->mode)) {
                $studlyMode = studly($this->mode);
                $reflectionClass = new \ReflectionClass(static::class);
                // 和检查器同存放位置
                $modeClass = $reflectionClass->getNamespaceName() . "\\{$studlyMode}Mode";
                if (class_exists($modeClass)) {
                    $mode = new $modeClass($this);
                }
            } else {
                $mode = $this->mode;
            }
        }

        if ($mode) {
            $mode->setStrict($this->strict);
            $mode->setFields($this->fields, $this->defaultValues);
            return $mode->handle($request, $routeParams);
        }

        // 绑定route参数
        if ($routeParams) {
            $this->routeParams = $routeParams;
        }

        $params = $this->getFieldParams($request);

        $handled = new ArrayObject();

        if (!$this->strict) {
            $fields = array_merge($this->fields, array_diff(array_keys($params), $this->fields));
        } else {
            // 严格模式时，只接受指定的字段输入
            // 一般地，需要做必输检查时，需要启用严格模式
            $fields = $this->fields;
        }

        // 根据指定的字段或者表单字段验证
        // 字段检查有先后，可以根据表单位置来调整，或者通过设置fields属性来调整这个顺序
        foreach ($fields as $field) {
            // 
            $value = $params[$field];
            // 检查各字段，有才检查，表单提交有或通过指定fields
            $handleMethod = 'handle' . studly($field);

            if (method_exists($this, $handleMethod)) {
                $callable = [$this, $handleMethod];
            } else {
                $callable = null;
            }

            // } else { // 有独立的mode类，则忽略inspecter类中待mode的方法
            //     // mode中做检查
            //     if (method_exists($mode, $handleMethod)) { // 在模式中实现
            //         $callable = [$mode, $handleMethod];
            //     } elseif (method_exists($this, $handleMethod)) { // 在主对象中实现
            //         $callable = [$this, $handleMethod];
            //     } else {
            //         $callable = null;
            //     }
            // }

            if (is_callable($callable)) {
                // 
                $processed = call_user_func($callable, $value, $field, $handled); //$this->{$method}($value, $field, $handled);

                // 没有返回值的，放弃该项
                if (!is_null($processed)) {
                    // 返回generator函数，一般是返回重新定义的多个key和它的值
                    if ($processed instanceof \Closure) {
                        $handled = new ArrayObject(array_merge((array) $handled, iterator_to_array($processed($handled))));
                    } else { // 返回普通的值，一般是保持单key值
                        $tableField = $this->tableField2FormField($field, true);
                        $handled[$tableField] = $processed;
                    }
                }
            } else {
                $tableField = $this->tableField2FormField($field, true);
                $handled[$tableField] = $params[$field];
            }
        }

        unset($params, $fields);

        // 关联检查
        if (!empty($this->finalizes)) {
            foreach ($this->finalizes as $fn) {
                $fn($handled);
            }
        }

        // 预设值
        foreach ($this->defaultValues as $key => $value) {
            $tableField = $this->tableField2FormField($key, true);
            if (!isset($handled[$tableField])) {
                $handled[$tableField] = $value;
            }
        }

        // 主检查器再次加工结果
        if (method_exists($this, 'handled')) {
            $handled = $this->handled($handled);
        }

        return $handled;
    }

    /**
     * Get input field value
     *
     * 默认返回reqeust的所有参数，可按需在子类覆盖重写，做一定的过滤
     *
     * @param ServerRequestInterface $request
     * @return array
     */
    protected function getFieldParams(ServerRequestInterface $request): array
    {
        return $request->getParams();
    }

    /**
     * Get route parameter value
     * 
     * @param string $key
     * @return mixed
     */
    protected function getRouteParams(string $key)
    {
        return $this->routeParams[$key] ?? null;
    }
}
