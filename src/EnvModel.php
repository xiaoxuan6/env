<?php
/**
 * Date: 2019/2/22
 * Time: 17:52
 */
namespace James\Env;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Request;

class EnvModel extends Model
{
    protected $primaryKey = 'id';
    protected $keyType = 'int';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }


    public function paginate()
    {
        $perPage = Request::get('per_page', 20);
        $page = Request::get('page', 1);
        $key = Request::get('key', '');
        $start = ($page - 1) * $perPage;
        $data = $this->getEnv('', $key);
        $list = array_slice($data, $start, $perPage);
        $list = static::hydrate($list);
        $paginator = new LengthAwarePaginator($list, count($data), $perPage);
        $paginator->setPath(url()->current());
        return $paginator;
    }

    public static function with($relations)
    {
        return new static;
    }

    public function findOrFail($id)
    {
        $item = $this->getEnv($id);
        return static::newFromBuilder($item);
    }


    public function save(array $options = [])
    {
        $data = $this->getAttributes();

        return $this->setEnv($data['key'], $data['value']);
    }

    /**
     * Get .env variable.
     * @param null $id
     * @return array|mixed
     */
    private function getEnv($id = null, $key = null)
    {
        $string = file(self::getEnvFilePath());
        $array = [];
        foreach ($string as $k => $one) {
            $strings = explode("=", str_replace("\n", "", $one), 2);
            list($index, $value) = (count($strings) == 2 ? $strings : [current($strings),'']);
            if ($index) {
                $array[] = ['id' => $k + 1, 'key' => $index, 'value' => isset($value) ? $value : null];
            }
        }

        if($id) {
            $data = collect($array)->where('id', $id)->toArray();
            return $data ? current($data) : [];
        }elseif($key) {
            $data = collect($array)->where('key', $key)->toArray();
            return $data ? current($data) : [];
        }else
            return $array;
    }

    /**
     * Update or create .env variable.
     * @param $key
     * @param $value
     * @return bool
     */
    private function setEnv($key, $value)
    {
        $array = $this->getEnv();
        $index = array_search($key, array_column($array, 'key'));
        if ($index !== false) {
            $array[$index]['value'] = $value; // 更新
        } else {
            array_push($array, ['key' => $key, 'value' => $value]); // 新增
        }
        return $this->saveEnv($array);
    }

    /**
     * Save .env variable.
     * @param $array
     * @return bool
     */
    public function saveEnv($contents)
    {
        $contentArray = collect($contents)->map(function ($value, $index) {
            return "{$index}={$value}";
        })->toArray();

        $content = implode(PHP_EOL, $contentArray);
        file_put_contents(self::getEnvFilePath(), $content);
        return true;
    }

    /**
     * Delete .env variable
     * @param $id
     * @return bool
     */
    protected function isDel($id){
        $data = $this->getEnv();
        $old_ids = array_column($data, 'id');

        if(is_array($id)){
            foreach ($id as $value){
                $index = array_search($value, $old_ids);
                if($index === false )
                    continue;
                else
                    unset($data[$index]);
            }
        }else{
            $index = array_search($id, $old_ids);
            if($index === false )
                return false;
            unset($data[$index]);
        }

        if($this->saveEnv($data))
            return true;
        else
            return false;
    }

    /**
     * 获取.env
     * @return string
     */
    private static function getEnvFilePath()
    {
        return Container::getInstance()->environmentPath() . DIRECTORY_SEPARATOR .
            Container::getInstance()->environmentFile();
    }
}