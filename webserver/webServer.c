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

void getCommand(char *, const char *);
void Error_Quit(char const*);
void getResponse(int );
void sig_handler(int);

int sock_fd1;

void sig_handler(int sig_num)
{
  close(sock_fd1); 
  exit(0);
}

char response_not_ok[15000] = "HTTP/1.1 404 Not Found\r\n"
			       "Content-Length: 0\r\n"
			      "Content-Type: text/html; charset=UTF-8\r\n\r\n";

char response_ok[15000] = "HTTP/1.1 200 OK\r\n"
		          "Content-Type: text/html; charset=UTF-8\r\n\r\n"
			  "<!DOCTYPE html><html><head><title></title>"
			  "<body>";
	    
	 
void Error_Quit(char const * msg) {
    fprintf(stderr, "WEBSERV: %s\n", msg);
    exit(EXIT_FAILURE);
}

int main(int c, char* argv[])
{

  signal(SIGINT, sig_handler);
  char port_number[10];
  strcpy(port_number,argv[1]);
  int port = atoi(port_number);
  printf("port no %i \n", port);

  int one = 1, sock_fd2;
  struct sockaddr_in s_address, c_address;
  socklen_t sin_len = sizeof(c_address);
 
  sock_fd1 = socket(AF_INET, SOCK_STREAM, 0);
  if (sock_fd1 < 0)
    err(1, "can't open socket");
 
  //setsockopt(sock, SOL_SOCKET, SO_REUSEADDR, &one , sizeof(int));
 
  s_address.sin_family = AF_INET;
  s_address.sin_addr.s_addr = INADDR_ANY;
  s_address.sin_port = htons(port);

  pid_t pid;
 
  if (bind(sock_fd1, (struct sockaddr *) &s_address, sizeof(s_address)) == -1) {
    close(sock_fd1);
    err(1, "Can't bind");
  }
 
  listen(sock_fd1, 5);
  while (1) {
    sock_fd2 = accept(sock_fd1, (struct sockaddr *) &c_address, &sin_len);
    printf("got connection\n");
 
    if (sock_fd2 == -1) {
      perror("Can't accept");
      continue;
    }

    if ( (pid = fork()) == 0 ) 
    {
	    if ( close(sock_fd1) < 0 )
		Error_Quit("Error closing listening socket in child.");
		    
	    getResponse(sock_fd2);
	
	    if ( close(sock_fd2) < 0 )
		Error_Quit("Error closing connection socket.");
	    exit(EXIT_SUCCESS);
    }

    if ( close(sock_fd2) < 0 )
        Error_Quit("Error closing connection socket in parent.");
    //waitpid(-1, NULL, WNOHANG);
  }
}

void getResponse(int fd)
{
	char *p, *q;
	int buffer_size = 1024;    
   	char *buffer_1 = malloc(buffer_size);
	recv(fd,buffer_1,buffer_size,0);
	printf("*********************\n");	
	printf("%s\n", buffer_1);
	printf("*********************\n");

	char *output = malloc(strlen(buffer_1)+1);
	getCommand(output, buffer_1);
	
	char *line[3];
	line[0] = strtok(output," \t\n");
	line[1] = strtok (NULL, " \t");
        line[2] = strtok (NULL, " \t\n");
	char * line2[1]; 
	line2[0] = strtok(line[1],"/");
	printf("get line %s \n",line[0]);
	printf("exec line %s \n",line[1]);
	printf("http line %s \n",line[2]);
	int h = strncmp(line[0], "GET /exec/", 10);
	int e = strncmp(line[1], "/exec/", 6);
	printf("#################");
	printf("h and e %d & %d\n",h,e);
	printf("#################");
	if(strncmp(line[0], "GET", 10)==0 && strncmp(line[2], "HTTP/1.1", 8)==0 )
        {
	    printf("inside if");
            if(line[1])
	    {
		char l2[] = "\n</bosdy></html>\r\n";
		char *cmd = strstr(line[1],"/");
		cmd = cmd + 6;
		
		FILE* file = popen(cmd, "r");
		char buffer[10000];
		
		while( fgets( buffer, 10000,  file ) ) {
			strncat(response_ok, buffer,sizeof(buffer));
        		fprintf( stdout, "%s", buffer  );	
    		}
				
		strcat(response_ok, l2);
	    	printf("######\n");
		pclose(file);
		write(fd,response_ok,strlen(response_ok));
	   }
	   else
	   {
		write(fd,response_not_ok,strlen(response_not_ok));
	   }
	}
	else
	{	printf("inside else");
		write(fd,response_not_ok,strlen(response_not_ok));
	}
}	


void getCommand(char *out, const char *in)
{
        char x, y;
        while (*in) {
                if ((*in == '%') && ((x = in[1]) && (y = in[2])) && (isxdigit(x) && isxdigit(y))) {
                        if (x >= 'a')
                                x -= 'a'-'A';
                        if (x >= 'A')
                                x -= ('A' - 10);
                        else
                                x -= '0';
                        if (y >= 'a')
                                y -= 'a'-'A';
                        if (y >= 'A')
                                y -= ('A' - 10);
                        else
                                y -= '0';
                        *out++ = 16*x+y;
                        in+=3;
                } else if (*in == '+') {
                        *out++ = ' ';
                        in++;
                } else {
                        *out++ = *in++;
                }
        }
        *out++ = '\0';
}




