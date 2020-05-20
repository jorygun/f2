<?php
namespace DigitalMx;



	defined ('URL_REGEX') or
			define ('URL_REGEX', '/(?<!["\'>])https?\:\/\/[\/\w\.\-\(\)\%\#\:\+]+([\?]+[\w\.\=\&\-\(\)\:\%\#\+]+)?/' );
			/* Must start with not a quote or > (end of a link)
				followed by http or https://
				forllowed by any number of \w . - ( ) % #
				possibly followed by a ? and then any number of \w.=&-():% or #
			*/

	defined ('BRNL') or
			define ('BRNL',"<br>\n");
	defined ('CRLF') or
			define ('CRLF',"\r\n");
	defined ('BR') or
			define ('BR',"<br>\n");
	defined ('NL') or
			define ('NL',"\n");
	defined ('LF') or
			define ('LF', "\n");
		
