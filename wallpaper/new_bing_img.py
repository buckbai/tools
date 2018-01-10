import requests
import ctypes
import os
from urllib.parse import urlparse
from datetime import date, timedelta
from zipfile import ZipFile, ZIP_DEFLATED


def get_img_url():
    url = 'https://www.bing.com/HPImageArchive.aspx?format=js&idx=0&n=1&mkt=zh-CN'
    r = requests.get(url)
    path = r.json()['images'][0]['url']
    url_o = urlparse(url)

    return url_o[0] + '://' + url_o[1] + path


def download_img(url):
    img_file = date.today().isoformat() + url[url.rindex('.'):]
    r = requests.get(url)
    with open(img_file, 'wb') as f:
        f.write(r.content)

    return img_file


def change_wallpaper(img_file):
    SPI_SETDESKWALLPAPER = 0x14
    SPIF_UPDATEINIFILE = 0x1
    ctypes.windll.user32.SystemParametersInfoW(SPI_SETDESKWALLPAPER, 0, os.path.abspath(img_file), SPIF_UPDATEINIFILE)


def clean_img(img):
    with ZipFile('bing_images.zip', 'a', ZIP_DEFLATED) as myzip:
        if img not in myzip.namelist():
            myzip.write(img)
    os.remove(img)


def check_img(d):
    for file in os.listdir():
        if file.startswith(d.isoformat()):
            return file
    return None


if __name__ == '__main__':
    today = date.today()
    yesterday = today - timedelta(days=1)
    image = check_img(yesterday)
    if image:
        clean_img(image)

    image = check_img(today)
    if not image:
        img_url = get_img_url()
        image = download_img(img_url)
    change_wallpaper(image)

