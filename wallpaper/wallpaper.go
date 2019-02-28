package main

import (
	"encoding/json"
	"fmt"
	"io/ioutil"
	"math/rand"
	"net/http"
	"os"
	"path/filepath"
	"syscall"
	"unsafe"
)

const (
	host    = "https://cn.bing.com"
	urlInfo = "https://cn.bing.com/HPImageArchive.aspx?format=js&idx=0&n=1&nc=1551335968489&pid=hp"

	SPI_SETDESKWALLPAPER = 0x0014
	SPIF_UPDATEINIFILE   = 0x0001
)

var (
	user32               = syscall.NewLazyDLL("user32.dll")
	systemParametersInfo = user32.NewProc("SystemParametersInfoW")
)

// Image url
type Image struct {
	Images []struct {
		URL string `json:"url"`
	} `json:"images"`
}

var image Image
var temp = os.Getenv("Temp")

func init() {
	if temp == "" {
		fmt.Println("not found temp folder.")
		os.Exit(1)
	}
	temp = filepath.Join(temp, fmt.Sprintf("%x", rand.Uint64()))
}

func main() {
	// get picture url
	resp, err := http.Get(urlInfo)
	if err != nil {
		fmt.Println(err)
		os.Exit(1)
	}
	b, err := ioutil.ReadAll(resp.Body)
	resp.Body.Close()
	if err != nil {
		fmt.Println(err)
		os.Exit(1)
	}
	// json decode
	json.Unmarshal(b, &image)
	url := host + image.Images[0].URL

	// get image data
	resp, err = http.Get(url)
	if err != nil {
		fmt.Println(err)
		os.Exit(1)
	}
	b, err = ioutil.ReadAll(resp.Body)
	resp.Body.Close()

	// write file
	err = ioutil.WriteFile(temp, b, 0644)
	if err != nil {
		fmt.Println(err)
		os.Exit(1)
	}

	// set wallpaper
	ret, _, _ := systemParametersInfo.Call(SPI_SETDESKWALLPAPER,
		uintptr(0),
		uintptr(unsafe.Pointer(syscall.StringToUTF16Ptr(temp))),
		uintptr(SPIF_UPDATEINIFILE))
	if ret != 1 {
		fmt.Println("set wallpaper error.")
		os.Exit(1)
	}
}
