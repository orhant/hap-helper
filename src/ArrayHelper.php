<?php 
namespace dicr\helper;

/**
 * Работа с массивами.
 * 
 * @author Igor (Dicr) Tarasov <develop@dicr.org>
 * @version 180601
 */
class ArrayHelper extends \yii\helpers\ArrayHelper {

	/**
	 * Удаляет элемент из массива.
	 * В отличие от оригинала поддерживает комплексный ключ.
	 * 
	 *  @param $array array исходный массив
	 *  @param $path string|array ключ для удаления. Может быть строкой с путем ключа, разделенным "." или массивом.
	 */
	public static function remove(&$array, $path, $default=null) {
		
		if (!is_array($path)) {
			$path = explode('.', $path);
		}

		while (count($path) > 1) {
			$key = array_shift($path);
			if (!isset($array[$key]) || !is_array($array[$key])) {
				return $default;
			}
			$array = &$array[$key];
		}
		
		$ret = $array[$key] ?? $default;
		unset($array[$key]);
		
		return $ret;
	}
}