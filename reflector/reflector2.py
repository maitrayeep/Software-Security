#! /usr/bin/env python

from scapy.all import *
import sys, getopt

interface = ''
victim_ip = ''
victim_ethernet= ''
reflector_ip = ''
reflector_ethernet = ''

def sniff_packets(packet_sniffed):
		 	
	packet1 = packet_sniffed[0]
		
	if (packet1[Ether].src != reflector_ethernet and packet1[Ether].src != victim_ethernet):
		
		if TCP in packet1:
			TCP_packet(packet1)
		elif UDP in packet1:
			UDP_packet(packet1)
		elif ICMP in packet1:
			ICMP_packet(packet1)
		elif ARP in packet1:
			ARP_packet(packet1)
		else:
			del packet_sniffed
		return

def TCP_packet(packet_1):
	
	if packet_1[IP].dst == victim_ip:
	
		source_port = packet_1[TCP].sport
		dest_port = packet_1[TCP].dport
		aEth = packet_1[Ether].src 
		aIP = packet_1[IP].src
		packet_new = packet_1
		del packet_new[IP].chksum
		del packet_new[TCP].chksum
		packet_new[Ether].src = reflector_ethernet ; packet_new[Ether].dst = aEth
		packet_new[IP].src = reflector_ip ; packet_new[IP].dst = aIP
		packet_new = packet_new.__class__(str(packet_new))
	
		sendp(packet_new, iface = interface)	


	elif packet_1[IP].dst == reflector_ip:
	
		aEth = packet_1[Ether].src
		aIP = packet_1[IP].src
		source_port = packet_1[TCP].sport
		dest_port = packet_1[TCP].dport
	
		packet_new = packet_1
		del packet_new[IP].chksum
		del packet_new[TCP].chksum
		packet_new[Ether].src = victim_ethernet ; packet_new[Ether].dst = aEth
		packet_new[IP].src = victim_ip ; packet_new[IP].dst = aIP
		packet_new = packet_new.__class__(str(packet_new))
		
		sendp(packet_new, iface = interface)	
	
	
	elif packet_1[IP].dst[-3:] == "255":

		aEth = packet_1[Ether].src 
		aIP = packet_1[IP].src
		source_port = packet_1[TCP].sport
		dest_port = packet_1[TCP].dport
		packet_new = packet_1
		del packet_new[IP].chksum
		del packet_new[TCP].chksum
		packet_new[Ether].src = reflector_ethernet ; packet_new[Ether].dst = aEth
		packet_new[IP].src = reflector_ip ; packet_new[IP].dst = aIP
		packet_new = packet_new.__class__(str(packet_new))

		sendp(packet_new, iface = interface)	

	else:
		del packet_1
	return

		
def UDP_packet(packet_1):
	
	if packet_1[IP].dst == victim_ip:
	
		source_port = packet_1[UDP].sport
		dest_port = packet_1[UDP].dport
		aEth = packet_1[Ether].src 
		aIP = packet_1[IP].src
		packet_new = packet_1
		del packet_new[IP].chksum
		del packet_new[UDP].chksum
		packet_new[Ether].src = reflector_ethernet ; packet_new[Ether].dst = aEth
		packet_new[IP].src = reflector_ip ; packet_new[IP].dst = aIP
		packet_new = packet_new.__class__(str(packet_new))
	
		sendp(packet_new, iface = interface)	


	elif packet_1[IP].dst == reflector_ip:
	
		aEth = packet_1[Ether].src 
		aIP = packet_1[IP].src
		source_port = packet_1[UDP].sport
		dest_port = packet_1[UDP].dport
	
		packet_new = packet_1
		del packet_new[IP].chksum
		del packet_new[UDP].chksum
		packet_new[Ether].src = victim_ethernet ; packet_new[Ether].dst = aEth
		packet_new[IP].src = victim_ip ; packet_new[IP].dst = aIP
		packet_new = packet_new.__class__(str(packet_new))
		
		sendp(packet_new, iface = interface)	
	
	
	elif packet_1[IP].dst[-3:] == "255":

		aEth = packet_1[Ether].src
		aIP = packet_1[IP].src
		source_port = packet_1[UDP].sport
		dest_port = packet_1[UDP].dport
		packet_new = packet_1
		del packet_new[IP].chksum
		del packet_new[UDP].chksum
		packet_new[Ether].src = reflector_ethernet ; packet_new[Ether].dst = aEth
		packet_new[IP].src = reflector_ip ; packet_new[IP].dst = aIP
		packet_new = packet_new.__class__(str(packet_new))

		sendp(packet_new, iface = interface)	

	else:
		del packet_1
	return


def ICMP_packet(packet_1):
	
	if packet_1[IP].dst == victim_ip:
	
		aEth = packet_1[Ether].src 
		aIP = packet_1[IP].src
		packet_new = packet_1
		del packet_new[IP].chksum
		del packet_new[ICMP].chksum
		packet_new[Ether].src = reflector_ethernet ; packet_new[Ether].dst = aEth
		packet_new[IP].src = reflector_ip ; packet_new[IP].dst = aIP
		packet_new = packet_new.__class__(str(packet_new))
	
		sendp(packet_new, iface = interface)	


	elif packet_1[IP].dst == reflector_ip:
	
		aEth = packet_1[Ether].src 
		aIP = packet_1[IP].src
	
		packet_new = packet_1
		del packet_new[IP].chksum
		del packet_new[ICMP].chksum
		packet_new[Ether].src = victim_ethernet ; packet_new[Ether].dst = aEth
		packet_new[IP].src = victim_ip ; packet_new[IP].dst = aIP
		packet_new = packet_new.__class__(str(packet_new))
		
		sendp(packet_new, iface = interface)	
	
	
	elif packet_1[IP].dst[-3:] == "255":

		aEth = packet_1[Ether].src 
		aIP = packet_1[IP].src
		
		packet_new = packet_1
		del packet_new[IP].chksum
		del packet_new[ICMP].chksum
		packet_new[Ether].src = reflector_ethernet ; packet_new[Ether].dst = aEth
		packet_new[IP].src = reflector_ip ; packet_new[IP].dst = aIP
		packet_new = packet_new.__class__(str(packet_new))

		sendp(packet_new, iface = interface)	

	else:
		del packet_1
	return

def ARP_packet(packet_1):
		
	if packet_1[ARP].op == 1:
			 
		if packet_1[ARP].pdst == reflector_ip:

			aEth = packet_1[ARP].hwsrc 
			aIP = packet_1[ARP].psrc
	
			packet_new = packet_1
			packet_new[Ether].src = reflector_ethernet ; packet_new[Ether].dst = aEth
			packet_new[ARP].op = 2
			packet_new[ARP].hwsrc = reflector_ethernet ; packet_new[ARP].psrc = reflector_ip
			packet_new[ARP].hwdst = aEth ; packet_new[ARP].pdst = aIP
	
			sendp(pkt_send, iface = interface)
		
		elif packet_1[ARP].pdst == victim_ip:
		
			aEth = packet_1[ARP].hwsrc 
			aIP = packet_1[ARP].psrc
			packet_new = packet_1
			packet_new[Ether].src = victim_ethernet ; packet_new[Ether].dst = aEth
			packet_new[ARP].op = 2
			packet_new[ARP].hwsrc = victim_ethernet ; packet_new[ARP].psrc = victim_ip	
			packet_new[ARP].hwdst = aEth ; packet_new[ARP].pdst = aIP
			sendp(packet_new, iface = interface)				
		
		else:
			del packet_1	
	else:
		del packet_1
	
	return
	
def main(argv):

	global interface
	global victim_ip
	global victim_ethernet
	global reflector_ip
	global reflector_ethernet
	
	try:
		opts, args = getopt.getopt(argv,"ha:b:c:d:e:",["interface=","victim-ip=","victim-ethernet=","reflector-ip=","reflector-ethernet="])
	except getopt.GetoptError:
		sys.exit(2)

	for opt, arg in opts:
		if opt == '-h':
			sys.exit()
		elif opt in ("-a", "--interface"):
			interface = arg
      		elif opt in ("-b", "--victim-ip"):
         		victim_ip = arg
		elif opt in ("-c", "--victim-ethernet"):
         		victim_ethernet = arg
		elif opt in ("-d", "--reflector-ip"):
         		reflector_ip = arg
		elif opt in ("-e", "--reflector-ethernet"):
         		reflector_ethernet = arg

	sniff(prn = sniff_packets, iface = interface )
	return
		

if __name__ == "__main__":
	main(sys.argv[1:])
