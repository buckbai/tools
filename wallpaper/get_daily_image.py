from urllib import request, parse
import json
from datetime import date
from os.path import basename, splitext

def get_image():
	url = 'https://www.bing.com/HPImageArchive.aspx?format=js&idx=0&n=1&mkt=zh-CN'
	with request.urlopen(url) as response:
	    res = response.read()
	image_url = 'https://www.bing.com' + json.loads(res)['images'][0]['url']
	image = 'C:\\bingImages\\' + date.today().strftime('%Y%m%d') + splitext(basename(parse.urlparse(image_url).path))[1]
	request.urlretrieve(image_url, image)
