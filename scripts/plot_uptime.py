#!/usr/bin/python

import os
import re
import sys
import base64

from datetime import datetime
from datetime import timedelta

from numpy import *

import matplotlib
from matplotlib.dates import *
matplotlib.use('Agg')
from matplotlib.backends.backend_agg import FigureCanvasAgg as FigureCanvas
from matplotlib.figure import Figure
import matplotlib.dates as mdates


s1 = ['MDA6Mjc6MjI6NDQ6N0Y6MzY=', 'MDA6MTU6NkQ6M0M6QkE6MEI=', 'MDA6MTU6NkQ6M0M6QkE6Nzg=', 'MDA6MTU6NkQ6NzI6NTM6MDc=']
s2 = ['00:27:22:44:7F:36', '00:15:6D:3C:BA:0B', '00:15:6D:3C:BA:78', '00:15:6D:72:53:07']
s3 = ['station4', 'station1', 'station2', 'station3']
stas = {'MDA6MTU6NkQ6M0M6QkE6MEI=': 'sta1',
       'MDA6MTU6NkQ6M0M6QkE6Nzg=': 'sta2',
       'MDA6MTU6NkQ6NzI6NTM6MDc=': 'sta3',
       'MDA6Mjc6MjI6NDQ6N0Y6MzY=': 'sta4'}


startdt = datetime.strptime("20110814000000", "%Y%m%d%H%M%S")
startday = startdt.date()
enddt = datetime.strptime("20111030000000", "%Y%m%d%H%M%S")
endday = enddt.date()
oneday = timedelta(days=1)
totdays = (enddt - startdt).days



#startdir = os.path.realpath(sys.argv[1])
startdir = '.'
startdir = os.path.realpath(startdir)
outdir = os.path.normpath('/home/seabird/backup_data/results/')




####################
# CHECKINS
#

checkin_dt = dtype([('date', str_),
                    ('datenum', float),
                    ('sta1', int32),
                    ('sta2', int32),
                    ('sta3', int32),
                    ('sta4', int32)])

checkin_sta1_dates = []
checkin_sta2_dates = []
checkin_sta3_dates = []
checkin_sta4_dates = []

checkin_counts = zeros(totdays, dtype=checkin_dt)

getsta = re.compile('\d{4}-(.*)\.usage\.txt')

plotdates = []

# Counts first
incrday = startday
dayidx = 0
while incrday < endday:
    files = os.listdir(startdir + "/" + incrday.strftime('%Y%m%d'))
    checkin_counts[dayidx]['date'] = incrday.strftime('%Y%m%d')
    checkin_counts[dayidx]['datenum'] = date2num(incrday)
    for f in files:
        if f.find('usage') == -1:
            continue
        thesta = stas[getsta.findall(f)[0]]
        checkin_counts[dayidx][thesta] += 1
        chdy = datetime.strptime(incrday.strftime('%Y%m%d') + f.split('-')[0] + '00', '%Y%m%d%H%M%S')
        if thesta == 'sta1':
            checkin_sta1_dates.append(chdy)
        elif thesta == 'sta2':
            checkin_sta2_dates.append(chdy)
        elif thesta == 'sta3':
            checkin_sta3_dates.append(chdy)
        else:
            checkin_sta4_dates.append(chdy)
        
    plotdates.append(incrday)
    incrday += oneday
    dayidx += 1


for sta in stas.values():
    fig = Figure(figsize=(16,8), dpi=72)
    canvas = FigureCanvas(fig)
    ax = fig.add_subplot(111)
    ax.plot(plotdates, checkin_counts[sta], 'x-') #, markeredgewidth=0.25)
    #months = mdates.MonthLocator() # every month
    #monthsFmt = mdates.DateFormatter('%b %Y')
    #ax.xaxis.set_major_locator(months)
    #ax.xaxis.set_major_formatter(monthsFmt)
    fig.autofmt_xdate()
    #ax.set_ylim([0, 100])
    ax.set_ylabel("Number of checkins")
    ax.set_title(sta)
    fig.subplots_adjust(left=0.04, bottom=0.10, right=0.98, \
                        top=0.95, wspace=0.20, hspace=0.00)
    canvas.print_png(outdir + "/checkin_count_" + sta + ".png")


fig = Figure(figsize=(16,8), dpi=72)
canvas = FigureCanvas(fig)
ax = fig.add_subplot(111)
ax.plot(plotdates, checkin_counts['sta2'], 'x-', plotdates, checkin_counts['sta4'], 'x-') #, markeredgewidth=0.25)
#months = mdates.MonthLocator() # every month
#monthsFmt = mdates.DateFormatter('%b %Y')
#ax.xaxis.set_major_locator(months)
#ax.xaxis.set_major_formatter(monthsFmt)
fig.autofmt_xdate()
#ax.set_ylim([0, 100])
ax.set_ylabel("Number of checkins")
ax.set_title('sta2 and sta4')
fig.subplots_adjust(left=0.04, bottom=0.10, right=0.98, \
                    top=0.95, wspace=0.20, hspace=0.00)
canvas.print_png(outdir + "/checkin_count_STA2_STA4.png")



####################
# Connected / Not Connected
#


checkin_sta1_dates.sort()
checkin_sta2_dates.sort()
checkin_sta3_dates.sort()
checkin_sta4_dates.sort()

minutes5 = timedelta(minutes=5)
minutes15 = timedelta(minutes=15)

def find_no_checkins(datearr):
    retarr = []
    currdate = datearr[0]
    for i in range(1, len(datearr)):
        if datearr[i] - currdate > minutes15:
            adddate = currdate + minutes5
            while adddate < datearr[i]:
                retarr.append(adddate)
                adddate += minutes5
        currdate = datearr[i]
    return retarr


nochk_sta1_dates = find_no_checkins(checkin_sta1_dates)
nochk_sta2_dates = find_no_checkins(checkin_sta2_dates)
nochk_sta3_dates = find_no_checkins(checkin_sta3_dates)
nochk_sta4_dates = find_no_checkins(checkin_sta4_dates)

checksnochecks = [(checkin_sta1_dates, nochk_sta1_dates, 'sta1'), (checkin_sta2_dates, nochk_sta2_dates, 'sta2'), (checkin_sta3_dates, nochk_sta3_dates, 'sta3'), (checkin_sta4_dates, nochk_sta4_dates, 'sta4')]

for (check, nocheck, sta) in checksnochecks:
    fig = Figure(figsize=(16,8), dpi=72)
    canvas = FigureCanvas(fig)
    ax = fig.add_subplot(111)
    ax.plot(check, ones(len(check)), 'bx', nocheck, zeros(len(nocheck)), 'rx')
    ax.set_ylim([-1, 2])
    ax.set_xlim([startdt, enddt])
    fig.autofmt_xdate()
    ax.set_ylabel("Connected or not connected")
    ax.set_title(sta)
    fig.subplots_adjust(left=0.04, bottom=0.10, right=0.98, \
                        top=0.95, wspace=0.20, hspace=0.00)
    canvas.print_png(outdir + "/connected_" + sta + ".png")








####################
# UPTIMES
#


uptime_dt = dtype([('date', str_),
                    ('datenum', float),
                    ('uptime', int32),
                   ])


uptime_sta1_uptimes =[]
uptime_sta1_dates = []
uptime_sta2_uptimes = []
uptime_sta2_dates = []
uptime_sta3_uptimes = []
uptime_sta3_dates = []
uptime_sta4_uptimes = []
uptime_sta4_dates = []

#getuptime = re.compile('uptime=(.*?)&')
getuptime = re.compile('uptime=(\d*)d:(\d*)h:(\d*)m.*?&')
getdatetime = re.compile('datetime=(\d*)\D*')

# uptimes
incrday = startday
while incrday < endday:
    for stab64 in stas.keys():
        checkfile = startdir + "/" + incrday.strftime('%Y%m%d') + "/" + stab64 + ".allcheckins.txt"
        if os.path.exists(checkfile) is False:
            continue
        lines = open(checkfile).readlines()
        temp_uptimes = []
        temp_dates = []
        for line in lines:
            (day, hour, min) = getuptime.findall(line)[0]
            td = timedelta(days=int(day), hours=int(hour), minutes=int(min))
            thedt = datetime.strptime(getdatetime.findall(line)[0], '%Y%m%d%H%M%S')
            temp_dates.append(thedt)
            temp_uptimes.append(td.seconds + td.days * 24 * 3600)
        if stas[stab64] == 'sta1':
            uptime_sta1_uptimes.extend(temp_uptimes)
            uptime_sta1_dates.extend(temp_dates)
        elif stas[stab64] == 'sta2':
            uptime_sta2_uptimes.extend(temp_uptimes)
            uptime_sta2_dates.extend(temp_dates)
        elif stas[stab64] == 'sta3':
            uptime_sta3_uptimes.extend(temp_uptimes)
            uptime_sta3_dates.extend(temp_dates)
        else:
            uptime_sta4_uptimes.extend(temp_uptimes)
            uptime_sta4_dates.extend(temp_dates)    
    incrday += oneday

uptime_sta1 = array(uptime_sta1_uptimes, dtype=float) / (3600 * 24.)
uptime_sta2 = array(uptime_sta2_uptimes, dtype=float) / (3600 * 24.)
uptime_sta3 = array(uptime_sta3_uptimes, dtype=float) / (3600 * 24.)
uptime_sta4 = array(uptime_sta4_uptimes, dtype=float) / (3600 * 24.)

for sta in stas.values():
    fig = Figure(figsize=(16,8), dpi=72)
    canvas = FigureCanvas(fig)
    ax = fig.add_subplot(111)
    if sta == 'sta1':
        ax.plot(uptime_sta1_dates, uptime_sta1, 'x-') #, markeredgewidth=0.25)
    elif sta == 'sta2':
        ax.plot(uptime_sta2_dates, uptime_sta2, 'x-') #, markeredgewidth=0.25)
    elif sta == 'sta3':
        ax.plot(uptime_sta3_dates, uptime_sta3, 'x-') #, markeredgewidth=0.25)
    else:
        ax.plot(uptime_sta4_dates, uptime_sta4, 'x-') #, markeredgewidth=0.25)
    #months = mdates.MonthLocator() # every month
    #monthsFmt = mdates.DateFormatter('%b %Y')
    #ax.xaxis.set_major_locator(months)
    #ax.xaxis.set_major_formatter(monthsFmt)
    fig.autofmt_xdate()
    ax.set_xlim([startdt, enddt])
    ax.set_ylim([0, 50])
    ax.set_ylabel("Uptime in Days")
    ax.set_title(sta)
    fig.subplots_adjust(left=0.04, bottom=0.10, right=0.98, \
                        top=0.95, wspace=0.20, hspace=0.00)
    canvas.print_png(outdir + "/uptime_" + sta + ".png")



for sta in stas.values():
    fig = Figure(figsize=(16,8), dpi=72)
    canvas = FigureCanvas(fig)
    ax = fig.add_subplot(111)
    ax2 = ax.twinx()
    if sta == 'sta1':
        ax.plot(uptime_sta1_dates, uptime_sta1, 'x-') #, markeredgewidth=0.25)
        ax2.plot(plotdates, checkin_counts['sta1'], 'gx-')
    elif sta == 'sta2':
        ax.plot(uptime_sta2_dates, uptime_sta2, 'x-') #, markeredgewidth=0.25)
        ax2.plot(plotdates, checkin_counts['sta2'], 'gx-')
    elif sta == 'sta3':
        ax.plot(uptime_sta3_dates, uptime_sta3, 'x-') #, markeredgewidth=0.25)
        ax2.plot(plotdates, checkin_counts['sta3'], 'gx-')
    else:
        ax.plot(uptime_sta4_dates, uptime_sta4, 'x-') #, markeredgewidth=0.25)
        ax2.plot(plotdates, checkin_counts['sta4'], 'gx-')
    ax.set_xlim([startdt, enddt])
    ax.set_ylim([0, 50])
    ax.set_ylabel("Uptime in Days")
    ax2.set_ylabel("Number of Checkins")
    ax.set_title(sta)
    fig.autofmt_xdate()
    fig.subplots_adjust(left=0.04, bottom=0.10, right=0.95, \
                        top=0.95, wspace=0.20, hspace=0.00)
    canvas.print_png(outdir + "/dual_uptime_checkincount_" + sta + ".png")


fig = Figure(figsize=(16,8), dpi=72)
canvas = FigureCanvas(fig)
ax = fig.add_subplot(111)
ax2 = ax.twinx()
ax.plot(uptime_sta2_dates, uptime_sta2, 'x-', uptime_sta4_dates, uptime_sta4, 'x-') #, markeredgewidth=0.25)
ax2.plot(plotdates, checkin_counts['sta2'], 'x-', plotdates, checkin_counts['sta4'], 'x-') #, markeredgewidth=0.25)    fig.autofmt_xdate()
ax.set_xlim([startdt, enddt])
ax.set_ylim([0, 50])
ax.set_ylabel("Uptime in Days")
ax2.set_ylabel("Number of Checkins")
ax.set_title(sta)
fig.autofmt_xdate()
fig.subplots_adjust(left=0.04, bottom=0.10, right=0.95, \
                    top=0.95, wspace=0.20, hspace=0.00)
canvas.print_png(outdir + "/dual_uptime_checkincount_STA2_STA4.png")











####################
# on or off
#

uptime_sta1_dates.sort()
uptime_sta2_dates.sort()
uptime_sta3_dates.sort()
uptime_sta4_dates.sort()

downtime_sta1_dates = find_no_checkins(uptime_sta1_dates)
downtime_sta2_dates = find_no_checkins(uptime_sta2_dates)
downtime_sta3_dates = find_no_checkins(uptime_sta3_dates)
downtime_sta4_dates = find_no_checkins(uptime_sta4_dates)

upsanddowns = [(uptime_sta1_dates, downtime_sta1_dates, 'sta1'), (uptime_sta2_dates, downtime_sta2_dates, 'sta2'), (uptime_sta3_dates, downtime_sta3_dates, 'sta3'), (uptime_sta4_dates, downtime_sta4_dates, 'sta4')]

for (up, down, sta) in upsanddowns:
    fig = Figure(figsize=(16,8), dpi=72)
    canvas = FigureCanvas(fig)
    ax = fig.add_subplot(111)
    ax.plot(up, ones(len(up)), 'bx', down, zeros(len(down)), 'rx')
    ax.set_ylim([-1, 2])
    ax.set_xlim([startdt, enddt])
    fig.autofmt_xdate()
    ax.set_ylabel("On or off")
    ax.set_title(sta)
    fig.subplots_adjust(left=0.04, bottom=0.10, right=0.98, \
                        top=0.95, wspace=0.20, hspace=0.00)
    canvas.print_png(outdir + "/onoff_" + sta + ".png")


for i in range(4):
    (up, down, sta) = upsanddowns[i]
    (check, nocheck, sta) = checksnochecks[i]
    fig = Figure(figsize=(16,8), dpi=72)
    canvas = FigureCanvas(fig)
    ax = fig.add_subplot(111)
    ax.plot(up, ones(len(up)), 'bx', check, ones(len(check)) + 0.2, 'bx', \
            down, zeros(len(down)), 'rx', nocheck, zeros(len(nocheck)) + 0.2, 'rx')
    ax.set_ylim([-1, 2])
    ax.set_xlim([startdt, enddt])
    fig.autofmt_xdate()
    ax.set_ylabel("On or off vs conencted or not connected")
    ax.set_title(sta)
    print "%d vs %d" % (len(up), len(check))
    print "%d vs %d" % (len(down), len(nocheck))
    fig.subplots_adjust(left=0.04, bottom=0.10, right=0.98, \
                        top=0.95, wspace=0.20, hspace=0.00)
    canvas.print_png(outdir + "/vs_onoff_connected_" + sta + ".png")









######################################
# throughput ... this does not work!
#







thru_sta1_thrus = []
thru_sta1_dates = []
thru_sta2_thrus = []
thru_sta2_dates = []
thru_sta3_thrus = []
thru_sta3_dates = []
thru_sta4_thrus = []
thru_sta4_dates = []

#getuptime = re.compile('uptime=(.*?)&')
getthru = re.compile('NTR=([\d.]*)-(KB|MB)/s*?&')
getdatetime = re.compile('datetime=(\d*)\D*')

# throughputs
incrday = startday
while incrday < endday:
    for stab64 in stas.keys():
        checkfile = startdir + "/" + incrday.strftime('%Y%m%d') + "/" + stab64 + ".allcheckins.txt"
        if os.path.exists(checkfile) is False:
            continue
        lines = open(checkfile).readlines()
        temp_thrus = []
        temp_dates = []
        for line in lines:
            res = getthru.findall(line)
            if len(res) == 0:
                continue
            (thru, dr) = res[0]
            if dr == 'MB':
                thru = float(thru) * 1024
            else:
                thru = float(thru)
            thedt = datetime.strptime(getdatetime.findall(line)[0], '%Y%m%d%H%M%S')
            temp_dates.append(thedt)
            temp_thrus.append(thru)
        if stas[stab64] == 'sta1':
            thru_sta1_thrus.extend(temp_thrus)
            thru_sta1_dates.extend(temp_dates)
        elif stas[stab64] == 'sta2':
            thru_sta2_thrus.extend(temp_thrus)
            thru_sta2_dates.extend(temp_dates)
        elif stas[stab64] == 'sta3':
            thru_sta3_thrus.extend(temp_thrus)
            thru_sta3_dates.extend(temp_dates)
        else:
            thru_sta4_thrus.extend(temp_thrus)
            thru_sta4_dates.extend(temp_dates)    
    incrday += oneday


for sta in stas.values():
    fig = Figure(figsize=(64,16), dpi=72)
    canvas = FigureCanvas(fig)
    ax = fig.add_subplot(111)
    if sta == 'sta1':
        ax.plot(thru_sta1_dates, thru_sta1_thrus, 'x-') #, markeredgewidth=0.25)
    elif sta == 'sta2':
        ax.plot(thru_sta2_dates, thru_sta2_thrus, 'x-') #, markeredgewidth=0.25)
    elif sta == 'sta3':
        ax.plot(thru_sta3_dates, thru_sta3_thrus, 'x-') #, markeredgewidth=0.25)
    else:
        ax.plot(thru_sta4_dates, thru_sta4_thrus, 'x-') #, markeredgewidth=0.25)
    #months = mdates.MonthLocator() # every month
    #monthsFmt = mdates.DateFormatter('%b %Y')
    #ax.xaxis.set_major_locator(months)
    #ax.xaxis.set_major_formatter(monthsFmt)
    fig.autofmt_xdate()
    ax.set_xlim([startdt, enddt])
    #ax.set_ylim([0, 50])
    ax.set_ylabel("Throughput in KB/s")
    ax.set_title(sta)
    fig.subplots_adjust(left=0.04, bottom=0.10, right=0.98, \
                        top=0.95, wspace=0.20, hspace=0.00)
    canvas.print_png(outdir + "/throughput_" + sta + ".png")

