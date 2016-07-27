package main

import (
	"bytes"
	"encoding/json"
	"fmt"
	"github.com/achun/tom-toml"
	"log"
	"net"
	"os"
	"os/exec"
	"time"
)

var done chan error = make(chan error)
var ch chan int = make(chan int)

type Response2 struct {
	Data [][]string `json:"content"`
	Func string     `json:"function"`
}
type CheckD struct {
	DomainName string
	Flag       string
}

type CheckDslice struct {
	CheckDs []CheckD
}

func main() { //主函数
	conf, err := toml.LoadFile("config.toml")
	if err != nil {
		fmt.Println(err)
		return
	}
	netListen, err := net.Listen("tcp", conf["socket.ip"].String()+":"+conf["socket.port"].String())
	CheckError(err)
	defer netListen.Close()
	Log("Waiting for clients")
	for {
		conn, err := netListen.Accept()
		if err != nil {
			continue
		}
		Log(conn.RemoteAddr().String(), " tcp connect success")
		go handleConnection(conn) //并发连接
	}

}
func checkCname(data [][]string) string {
	var reData CheckDslice
	num := len(data)   //大小
	maxRunTimes := 100 //最大并发数
	everyDotimes := 10 //每次执行条数
	if num > 1000 {
		everyDotimes = num / maxRunTimes
	}
	everyRunTimes := num/everyDotimes + 1 //并发的次数
	if everyRunTimes > maxRunTimes {
		everyRunTimes = maxRunTimes
	}
	//fmt.Println(data)
	var start, end int
	f, err := exec.LookPath("php")
	if err != nil {
		fmt.Println("not install php")
	}
	conf, err := toml.LoadFile("config.toml")

	if err != nil {
		fmt.Println(err)
	}
	for i := 0; i < everyRunTimes; i++ {
		start = i * everyDotimes
		end = (i + 1) * everyDotimes
		if end > num {
			end = num
		}
		idArr := data[start:end]
		go goCheckCname(i, idArr, f, conf, &reData)
	}
	for i := 0; i < everyRunTimes; i++ {
		<-ch
		//fmt.Println("完毕", i)
	}
	reJson, err := json.Marshal(reData)
	if err != nil {
		fmt.Println("json err:", err)
	}
	//fmt.Println(string(reJson))
	return string(reJson)
}
func goCheckCname(Times int, Arr [][]string, f string, conf toml.Toml, reD *CheckDslice) {
	length := len(Arr)
	var rs string
	for i := 0; i < length; i++ {
		cmd := exec.Command(f, conf["socket.cli_file"].String(), "socket", "checkCname", Arr[i][0], Arr[i][1])
		var output bytes.Buffer
		cmd.Stdout = &output
		cmd.Start() //命令开始
		go func() {
			done <- cmd.Wait() //等待完成
		}()
		select {
		case <-done:
			rs = string(output.Bytes())
			if rs != "1" {
				rs = ""
			}
			reD.CheckDs = append(reD.CheckDs, CheckD{DomainName: Arr[i][0], Flag: rs})
			fmt.Println("成功执行")
		case <-time.After(time.Second * 5): //超时5s
			reD.CheckDs = append(reD.CheckDs, CheckD{DomainName: Arr[i][0], Flag: ""})
			fmt.Printf("超时5s")
			if err := cmd.Process.Kill(); err != nil {
				fmt.Println("failed to kill: %s, error: %s", cmd.Path, err)
			}
			go func() {
				<-done
			}()
		}
	}
	ch <- Times
}
func doFunc(funcName string, data [][]string) (rs string) {
	switch funcName {
	case "checkCname":
		rs = checkCname(data)
	}
	return
}
func handleConnection(conn net.Conn) { //Socket连接

	buffer := make([]byte, 1000000)
	n, err := conn.Read(buffer)
	res := &Response2{}
	json.Unmarshal([]byte(string(buffer[:n])), &res)
	function := res.Func
	rs := doFunc(function, res.Data)
	if err != nil {
		Log(conn.RemoteAddr().String(), " connection error: ", err)
	} else {
		conn.Write([]byte(string(rs)))
	}

	//Log(conn.RemoteAddr().String(), "receive data string:\n", string(buffer[:n]))
	conn.Close()

}
func Log(v ...interface{}) {
	log.Println(v...)
}

func CheckError(err error) {
	if err != nil {
		fmt.Fprintf(os.Stderr, "Fatal error: %s", err.Error())
		os.Exit(1)
	}
}
