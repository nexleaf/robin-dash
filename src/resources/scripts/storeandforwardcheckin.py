#!/usr/bin/python


#
# Add this to cron and for now hard code in the directory where the 
# First param is the location of the robin dash settinsg file
#    e.g.  /var/www/seabird/root/mesh
# Second param is the network name
#    e.g. mynetwork
#
#

import os
import sys
import curl
import pycurl
import os.path
import xml.etree.ElementTree as ET


# Check for the right args
if len(sys.argv) != 3:
	print "Please provide robindash root directory and networkname!"
	sys.exit(1)

# Check the path to the settings file
if not os.path.isdir(sys.argv[1]):
	print "Invalid directory: %s" % (sys.argv[1])
	print "Please make sure first parameter is robindash root directory!"
	sys.exit(1)

networksettingsfile = os.path.abspath(sys.argv[1]) + "/data/" + sys.argv[2] + ".xml"

# check the settings file is there
if not os.path.isfile(networksettingsfile):
	print "Invalid network settings file: %s" % (networksettingsfile)
	print "Please list correct network as second parameter!"
	sys.exit(1)

forwarddir = os.path.abspath(sys.argv[1]) + "/data/forward/"

# check the forward data is there
if not os.path.isdir(forwarddir):
	print "Invaid forward dir: %s" % (forwarddir)
	print "Check if it exists"
	sys.exit(1)


fd = open(networksettingsfile)
nsets = ET.parse(fd)
fd.close()

forwardurl = ""
for e in nsets.getiterator:
	if a.tag == 'storeandforward':
		forwardurl = a.text

if forwardurl == "":
	print "No foward url, stopping"
	sys.exit(0)

if not forwardurl.startswith("http://"):
	forwardurl = "http://" + forwardurl


for fname in os.listdir(forwarddir):
	try:
		fd = open(forwarddir + fname)
		getstr = fd.readline()
		fd.close()
		geturl = forwardurl + "/checkin-batman.php?" + getstr.strip()
		#
		c = curl.Curl()
		res = c.get(geturl)
		os.unlink(forwarddir + fname)
	except pycurl.error as e:
		# if it is an upload/network/server error, do nothing
		print "Upload Error: " % (e)
	except Exception as e:
		# there is maybe some other problem so we delete the file
		print "Unknown error: %s" % (e) 
		os.unlink(forwarddir + fname)



		
