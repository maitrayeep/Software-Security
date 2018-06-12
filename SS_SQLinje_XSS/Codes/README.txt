

Usage of the Library:

- All files should be placed in the same folder as the web service which is being wrapped.
  Required files: pctf-filter.php, xss_sql_filter.php, driver.php, .htaccess

- To configure the wrapper: ./pctf-filter.php <command> [<args>]
  For a list of available commands: './pctf-filter.php commands'
  For more info about a specific command: './pctf-filter.php help <command>' 
  Possible commands: commands, help, set, unset

- For using set to add a variable to filter: ./pctf-filter.php set <page> <type> <variable> <rule-id>
  where type is the request type and page is the php page of the original website where the request is headed.
  Example: ./pctf-filter.php set /index.php get msg 1

- For using unset to remove a variable from being filtered: ./pctf-filter.php unset <page> [<type> <variable>]
  Example: ./pctf-filter.php unset index.php get msg

- The configuration of the wrapper is stored in the file filter.json.


- This is a wrapper that sanitizes user input before sending the input
  to the actual page being requested.
 
How the wrapper works:
 
1. There is a .htaccess file in the same directory. The .htaccess file tells
 Apache to reroute all requests for .php pages to point to this page.
 
2. The wrapper checks filter.json for any rules that apply to the page being requested. The wrapper then sanitizes the appropriate inputs according to
 the rules.
 
3. After sanitizing the inputs, the wrapper includes the actual requested
 page and runs it.
 

- The library can also be used directly with the source code by importing ‘xss_sql_filter.php’ and using appropriate function to sanitize inputs before placing them in the source code.

- The xss_sql_filter is a class that has functions written to sanitize the input for XSS prevention. Apart from that we have included 3 other functions to sanitize input for SQL Injection Prevention as well.

The function names are as follows:

XSS Prevention:

- filter_Disallow($string)
	 
- filter_HTMLEntityEncode($string)
	 
- filter_For_Attributes($string)
	
- filter_For_Javascript($string)
	 
- filter_For_CSS($string)
	 
- filter_For_URL($string)

SQL Injection Prevention: 

- filter_SQL_Escaping($string)
	 
- filter_SQL_CompleteEscape($input)
	 
- filter_Char2Num_id($string)
