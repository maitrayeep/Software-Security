#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <sys/types.h> 
#include <sys/socket.h>
#include <netinet/in.h>
#include <err.h>
#include <sys/wait.h>
#include <string.h> 
#include <ctype.h>


//two socket descriptors
int sock_fd1, sock_fd2;

//function definitions
void getResponse(int );
void sig_handler(int );
char *getCommand(char *);
char convert_from(char);

//to handle SIGINT
void sig_handler(int sig_num)
{ 
	close(sock_fd1); 
	exit(0);
}

char response_not_ok[1000] = "HTTP/1.1 404 Not Found\r\n"
			    "Content-Length: 0\r\n"
			    "Content-Type: text/html; charset=UTF-8\r\n\r\n"
			    "<!DOCTYPE html><html><head><title></title>"
			    "<body>404 Not Found\n</body></html>\r\n";			  

int main(int c, char* argv[])
{
	signal(SIGINT, sig_handler);
	
	struct sockaddr_in s_address, c_address;
	socklen_t sin_len = sizeof(c_address);
	pid_t pid;
	
	//take port number from the command line argument and convert it to int
	char port_number[10];
	strcpy(port_number,argv[1]);
	int port = atoi(port_number);

	//create socket
	sock_fd1 = socket(AF_INET, SOCK_STREAM, 0);
	if (sock_fd1 < 0)
    		err(1, "Cannot Open Socket");
 
	s_address.sin_family = AF_INET;
	s_address.sin_addr.s_addr = INADDR_ANY;
	s_address.sin_port = htons(port);

	//do binding
	if (bind(sock_fd1, (struct sockaddr *) &s_address, sizeof(s_address)) != 0) 
	{
        	close(sock_fd1);
    		err(1, "Binding Problem !");
	}
	
	listen(sock_fd1, 5);
	while (1) 
	{
    		sock_fd2 = accept(sock_fd1, (struct sockaddr *) &c_address, &sin_len);
    		
 	 	if (sock_fd2 == -1) {
      			perror("Cannot accept");
      			continue;
    		}
		//fork to handle the request
		if ((pid = fork()) == 0) 
    		{
	    		close(sock_fd1);
	
	    		getResponse(sock_fd2);
	
			close(sock_fd2);
		
	    		exit(EXIT_SUCCESS);
    		}
		close(sock_fd2);
		waitpid(-1, NULL, WNOHANG);
	}
	return 0;
}

void getResponse(int fd)
{
	int buffer_size = 1024;    
   	char *buffer_1 = malloc(buffer_size);
	int p,q,r;
	
	recv(fd,buffer_1,buffer_size,0);

	char *line[3];
	line[0] = strtok(buffer_1," \t\n");
	line[1] = strtok (NULL, " \t");
       	line[2] = strtok (NULL, " \t\n");
	
	p = strncmp(line[0], "GET",3);
	q = strncmp(line[1], "/exec/",6);
	r = strncmp(line[2], "HTTP/1.1",8);
	
	char *line_1[2];
	line_1[0] = strtok(line[1],"/");
	line_1[1] = strtok (NULL, "\n");
		
	if(q != 0)
	{
		char *exec_line = malloc(strlen(line[1])+1);
		exec_line = getCommand(line[1]);
		q = strncmp(exec_line, "/exec",5);
	}	

	//check if request is valid
	if(p == 0 && r == 0 && q ==0)
	{	char response_ok[15000];
		char buffer[15000];
		int len;
		char *out=(char*)malloc(15000);
		char *cmd = malloc(strlen(line_1[1])+1);
		//decode the command
		cmd = getCommand(line_1[1]);
		strcat(cmd, " 2>&1");
		//execute the command
		FILE* file = popen(cmd, "r");
		
		while(fgets(buffer,15000,file)) 
		{
			strcat(out, buffer);
        	}
		
		len = strlen(out);
		sprintf(response_ok,"HTTP/1.1 200 OK\r\nContent-Length : %d\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n",len);
		sprintf(response_ok,out,sizeof(out));
		fflush(stdout);
	    	pclose(file);
			
		write(fd,response_ok,strlen(response_ok));
				
	}
	else
	{
		write(fd,response_not_ok,strlen(response_not_ok));
	}	
}


//code referred from www.geekhideout.com
char *getCommand(char *s) 
{
	char *q = s;
	char *buffer = malloc(strlen(s) + 1);
	char *buffer_1 = buffer;
	while(*q) 
	{
		if (*q == '%') 
		{
      			if (q[1] && q[2]) 
			{
        			*buffer_1++ = convert_from(q[1]) << 4 | convert_from(q[2]);
       				 q += 2;
      			}
    		} 
		else 
			if (*q == '+') 
			{ 
      				*buffer_1++ = ' ';
		    	} 
			else {
		      		*buffer_1++ = *q;
	    	        }
   		 	q++;
	}
	*buffer_1 = '\0';
	return buffer;
}
	
char convert_from(char c) 
{
	return isdigit(c) ? c - '0' : tolower(c) - 'a' + 10;
}
