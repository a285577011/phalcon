function bindEnter(event,objInput,perPage)
{
	if (event.keyCode==13)
	{
		var pageNum=objInput;
		gotoPageLib(pageNum,perPage);
	}
}

function gotoPage(objInput,perPage)
{
	var pageNum=$(objInput).parent().find("input.pl-text").val();
	gotoPageLib(pageNum,perPage);
}

function gotoPageLib(pageNum,perPage)
{
	var url=document.location.href;
	var page='';
	var pageIcon=url.indexOf("limit_start");
	if(pageNum=='' || isNaN(pageNum))
	{
		alert('请输入页码');
		return false;
	}
	var fuhao=url.indexOf("?");
	if(pageIcon==-1)
	{
		if(fuhao==-1)
		{
			page=url+'?limit_start='+perPage*(pageNum-1);
		}
		else
		{
			page=url+'&limit_start='+perPage*(pageNum-1);
		}
	}
	else
	{
		var number='';
		var start=url.indexOf("limit_start=");
		var end=url.indexOf("&",start);
		if(end==-1)
		{
			number=url.substring(Number(start)+12);
		}
		else
		{
			number=url.substr(Number(start)+12,end);
		}
		page=url.replace("limit_start="+number,"limit_start="+perPage*(pageNum-1));
	}
	window.location.href=page;
}