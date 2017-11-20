from os import listdir
from os.path import isfile, join
import ctypes
from datetime import date
import re

def change_wallpaper():
	folder = "C:\\bingImages"
	onlyfiles = [f for f in listdir(folder) if isfile(join(folder, f))]
	r = re.compile(date.today().strftime('%Y%m%d'))
	image = [x for x in onlyfiles if r.match(x)][0]
	SPI_SETDESKWALLPAPER = 0x14
	SPIF_UPDATEINIFILE   = 0x1
	image_path = join(folder, image)
	ctypes.windll.user32.SystemParametersInfoW(SPI_SETDESKWALLPAPER, 0, image_path, SPIF_UPDATEINIFILE)
