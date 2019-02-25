<?php
/**
 * Date: 2019/2/22
 * Time: 17:52
 */
namespace James\Env;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Request;

class EnvModel extends Model
{
    protected $primaryKey = 'id';
    protected $keyType = 'int';
    private $env;

    public function __construct(array $attributes = [])
    {
        $this->env = base_path('/.env');
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
        $string = file_get_contents($this->env);
        $string = array_filter(preg_split('/\n+/', $string));
        $array = [];
        foreach ($string as $k => $one) {
            $entry = explode("=", $one, 2);
            if (!empty($entry[0])) {
                $array[] = ['id' => $k + 1, 'key' => $entry[0], 'value' => isset($entry[1]) ? $entry[1] : null];
            }
        }
        if (empty($id) && empty($key)) {
            return $array;
        }

        if($id){
            $index = array_search($id, array_column($array, 'id'));
            return $array[$index];
        }elseif($key){
            $index = array_search($key, array_column($array, 'key'));
            return
                [$array[$index]];
        }

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
    private function saveEnv($array)
    {
        if (is_array($array)) {
            $newArray = [];
            $i = 0;
            foreach ($array as $env) {

                if (preg_match('/\s/', $env['value']) > 0 && (strpos($env['value'], '"') > 0 && strpos($env['value'], '"', -0) > 0)) {
                    $env['value'] = '"'.$env['value'].'"';
                }
                $newArray[$i] = $env['key']."=".$env['value'];
                $i++;
            }
            $newArray = implode("\n", $newArray);
            file_put_contents($this->env, $newArray);
            return true;
        }
        return false;
    }

    /**
     * Delete .env variable
     * @param $id int
     * @return bool
     */
    protected function del($id){
        $data = $this->getEnv();
        $index = array_search($id, array_column($data, 'id'));
        if($index === false )
            return false;

        unset($data[$index]);
        if($this->saveEnv($data))
            return true;
        else
            return false;
    }

    /**
     * DeleteAll .env variable
     * @param $ids array
     * @return bool
     */
    protected function deleteAll($ids){
        if(!is_array($ids))
            return false;

        $data = $this->getEnv();
        $old_ids = array_column($data, 'id');

        foreach ($ids as $value){
            $index = array_search($value, $old_ids);
            if($index === false )
                continue;
            else
                unset($data[$index]);
        }

        if($this->saveEnv($data))
            return true;
        else
            return false;
    }

}