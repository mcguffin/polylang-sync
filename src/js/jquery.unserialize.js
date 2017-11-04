/**
 * $.unserialize
 *
 * Takes a string in format "param1=value1&param2=value2" and returns an object { param1: 'value1', param2: 'value2' }. If the "param1" ends with "[]" the param is treated as an array.
 *
 * Example:
 *
 * Input:  param1=value1&param2=value2
 * Return: { param1 : value1, param2: value2 }
 *
 * Input:  param1[]=value1&param1[]=value2
 * Return: { param1: [ value1, value2 ] }
 *
 * @todo Support params like "param1[name]=value1" (should return { param1: { name: value1 } })
 */
(function($){
	$.unserialize = function(serializedString){
		var str = decodeURI(serializedString);
		var pairs = str.split('&');
		var obj = {}, p, idx, val;
		for (var i=0, n=pairs.length; i < n; i++) {
			p = pairs[i].split('=');
			idx = p[0];

			if (idx.indexOf("[]") == (idx.length - 2)) {
				// Eh um vetor
				var ind = idx.substring(0, idx.length-2)
				if (obj[ind] === undefined) {
					obj[ind] = [];
				}
				obj[ind].push(p[1]);
			}
			else {
				obj[idx] = p[1];
			}
		}
		return obj;
	};
})(jQuery);