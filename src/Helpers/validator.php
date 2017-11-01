<?php

function isNotEmpty($value)
{
	if (empty($value)) return false;
	$temp = trim($value);
	return !empty($temp);
}

/**
 * isLength
 *
 * @param	string
 * @return	bool
 */
function isLength($str, $params)
{
	if (empty($params))
	{
		throw new Exception("Параметр 'unique' проверки длины не указан");
	}

	if (!empty($params['unique']))
	{
		return strlen($str) == $params['unique'];
	}

	if (!empty($params['min']))
	{
		return strlen($str) >= $params['min'];
	}

	if (!empty($params['max']))
	{
		return strlen($str) <= $params['max'];
	}

	return true;
}

/**
 * isInteger
 *
 * @param	string
 * @return	bool
 */
function isInteger($str)
{
	if (empty($str)) $str = 0;
	return (bool) preg_match('/^[\-+]?[0-9]+$/', $str);
}

function isNumeric($str)
{
	if (empty($str)) $str = 0;
	return (bool) preg_match('/^[\-+]?[0-9,.]+$/', $str);
}

/**
 * Is a Natural number  (0,1,2,3, etc.)
 *
 * @param	string
 * @return	bool
 */
function isNatural($str)
{
	if (empty($str)) $str = 0;
	return (bool) preg_match('/^[0-9]+$/', $str);
}

// --------------------------------------------------------------------

/**
 * Is a Natural number, but not a zero  (1,2,3, etc.)
 *
 * @param	string
 * @return	bool
 */
function isNaturalNoZero($str)
{
	if ( ! preg_match( '/^[0-9]+$/', $str))
	{
		return FALSE;
	}

	if ($str == 0)
	{
		return FALSE;
	}

	return TRUE;
}

/**
 * isEmail
 *
 * @param	string
 * @return	bool
 */
function isEmail($mail)
{
	$exp = '/([A-Za-z0-9_\-]+\.)*[A-Za-z0-9_\-]+@([A-Za-z0-9][A-Za-z0-9\-]*[A-Za-z0-9]\.)+[A-Za-z]{2,4}/u';
	preg_match_all($exp, $mail, $pEmail);
	return isset($pEmail[0][0]) && !empty($pEmail[0][0]);
}

/**
 * isPhone
 *
 * @param	string
 * @return	bool
 */
function isPhone($str,$params = false)
{
	if (empty($params) && empty($params['pattern']))
	{
		$pattern = '/^7-[0-9]{3}-[0-9]{7}$/';
	}
	else
	{
		$pattern = $params['pattern'];
	}

	$bool =  preg_match($pattern, $str);
	
	return $bool;
}

/**
 * isUrl
 *
 * @param	string
 * @return	bool
 */
function isUrl($str)
{
	return (filter_var($str, FILTER_VALIDATE_URL) !== false);
}

/**
 * isDatetime
 *
 * @param	string
 * @return	bool
 */
function isDate($date, $options = [])
{
	if (empty($options) || empty($options['format']))
	{
		$options['format'] = 'Y-m-d H:i:s';
	}

	$d = DateTime::createFromFormat($options['format'], $date);
    return $d && $d->format($options['format']) == $date;
}

?>