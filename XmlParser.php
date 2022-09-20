<?php

trait XmlParser
{
	/**
	 * xmlToArray
	 *
	 * @param string $xml
	 * @param integer $index
	 * @return array
	 */
	public function xmlToArray(&$xml, &$index = 0)
	{
		$result = [];
		$lastIndex = $index;
		$flag = 0;

		while ($label = $this->getLabelName($xml, $index)) {

			if ($label[0] === '/') {
				if ($flag == 1) {
					$result[substr($label, 1)] = substr($xml, $lastIndex + 1, $index - $lastIndex - 2 - strlen($label));
				} else if ($flag == 2) {
					return $result;
				}

				$flag = 2;

			} else {
				$flag = 1;
				if (substr($xml, $index + 1, 1) === '<' && substr($xml, $index + 2, 1) !== '/') {
					$attrs = $this->getAttrs($label);
					if (isset($result[$label])) {
						if (!isset($result[$label][0])) {
							$result[$label] = [$result[$label]];
						}

						$result[$label][] = $attrs ? array_merge(['@attributes' => $attrs], $this->xmlToArray($xml, $index)) : $this->xmlToArray($xml, $index);
						
					} else {
						$result[$label] = $attrs ? array_merge(['@attributes' => $attrs], $this->xmlToArray($xml, $index)) : $this->xmlToArray($xml, $index);
					}

					$flag = 2;
				}
			}

			$lastIndex = $index;
		}

		return $result;
	}

	/**
	 * 获取属性
	 *
	 * @param string $label
	 * @return array
	 */
	public function getAttrs(&$label)
	{
		$attrs = [];
		if (strpos($label, ' ') !== false) {
			$attrArr = explode(' ', $label);
			
			foreach ($attrArr as $k => $attr) {
				if ($k == 0) {
					$label = $attr;
					continue;
				}
				if (strpos($attr, '=') !== false) {
					$arr = explode('=', $attr);
					$attrs[$arr[0]] = trim($arr[1], '"');
				} else {
					$attrs[$attr] = null;
				}
			}
		}

		return $attrs;
	}



	/**
	 * 获取xml标签名称
	 *
	 * @param string $xml
	 * @param int $index
	 * @return string|false
	 */
	public function getLabelName(&$xml, &$index)
	{
		$ltIndex = strpos($xml, '<', $index);
		if ($ltIndex === false) {
			return false;
		}
		$index = strpos($xml, '>', $ltIndex);

		return substr($xml, $ltIndex + 1, $index - 1 - $ltIndex);
	}

	/**
	 * 分批将XML字符串转换为UTF-8编码
	 *
	 * @param string $xml
	 * @param integer $length
	 * @return string
	 */
	public function convertToUtf8InBatches($xml, $length = 8192)
	{
		$result = '';
		while ($xml) {
			// 剩余长度小于要截取的长度, 直接处理
			if (strlen($xml) <= $length){
				$result .= $this->filterUtf8($xml);
				break;
			}
			// 获取指定长度$length后的第一个 ">" 位置
			$size = strpos($xml, '>', $length);
			if ($size === false) {
				break;
			}

			$size += 1;
			// 截取字符串并做转换处理
			$substr = substr($xml, 0, $size);
			$result .= $this->filterUtf8($substr);
			// 剩余部分循环
			$xml = substr($xml, $size);
		}

		return $result;
	}
}
