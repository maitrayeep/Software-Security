#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <sys/types.h> 
#include <sys/socket.h>
#include <netinet/in.h>
#include <netdb.h>
#include <arpa/inet.h>
#include <err.h>
#include <sys/wait.h>
#include<string.h> 
#include <ctype.h>

char *getCommand(char *);
char from_hex(char);

void main()
{
	char line[] = "/%65%78%65%63/echo%20hello";
	char *exec_line = malloc(strlen(line)+1);
	exec_line = getCommand(line);
	printf("exec line ..... %s",exec_line);
}

char *getCommand(char *str) 
{
	char *pstr = str, *buf = malloc(strlen(str) + 1), *pbuf = buf;
	while (*pstr) 
	{
		if (*pstr == '%') 
		{
      			if (pstr[1] && pstr[2]) 
			{
        			*pbuf++ = from_hex(pstr[1]) << 4 | from_hex(pstr[2]);
       				 pstr += 2;
      			}
    		} 
		else 
			if (*pstr == '+') 
			{ 
      				*pbuf++ = ' ';
		    	} 
			else {
		      		*pbuf++ = *pstr;
	    	        }
   		 	pstr++;
	}
	*pbuf = '\0';
	return buf;
}
	

/* Converts a hex character to its integer value */
char from_hex(char ch) 
{
	return isdigit(ch) ? ch - '0' : tolower(ch) - 'a' + 10;
}
