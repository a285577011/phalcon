function searchFormReset(form) {
	tform = $(form);
	tform.find(':input[type!="button"][type!="reset"][type!="submit"][type!="checkbox"]').val(null);
	 tform.find(':input[type="checkbox"]').prop('checked',false);
}
function check_form() {
	var errormsg = '';
	var error = false;
	var StartPrice = $('input[name="StartPrice"]').val();
	var EndPrice = $('input[name="EndPrice"]').val();
	var StartCommission =$('input[name="StartCommission"]').val();
	var EndCommission = $('input[name="EndCommission"]').val();
	if (StartPrice.length>0) {
		if(!(/^\d+$/.test(StartPrice))){
			error = true;
			errormsg = '起始价格必须为整数';
		}
		if (isNaN(StartPrice)) {
			error = true;
			errormsg = '起始价格必须为数字';
		} else if (StartPrice < 0) {
			$('input[name="StartPrice"]').val('0');
			error = true;
			errormsg = '起始价格必须大于0';
		}
	}
	if (EndPrice.length>0) {
		if(!(/^\d+$/.test(EndPrice))){
			error = true;
			errormsg = '结束价格必须为整数';
		}
		if (isNaN(EndPrice)) {
			error = true;
			errormsg = '结束价格必须为数字';
		} else if (parseFloat(EndPrice) < parseFloat(StartPrice)) {
			error = true;
			errormsg = '结束价格必须大于起始价格';
		}
		else if(parseFloat(EndPrice)==0){
			error = true;
			errormsg = '结束价格必须大于0!';
		}
	}
	if (StartCommission.length>0) {
		if(!(/^\d+$/.test(StartCommission))){
			error = true;
			errormsg = '起始佣金比例必须为整数';
		}
		if (isNaN(StartCommission)) {
			error = true;
			errormsg = '佣金比例必须为数字';
		} else if (StartCommission < 0) {
			$('input[name="StartCommission"]').val('0');
			error = true;
			errormsg = '佣金比例必须为0到100的整数！';
		}
	}
	if (EndCommission.length>0) {
		if(!(/^\d+$/.test(EndCommission))){
			error = true;
			errormsg = '结束佣金比例必须为整数';
		}
		if (isNaN(EndCommission)) {
			error = true;
			errormsg = '佣金比例必须为数字';
		} else if (parseFloat(EndCommission) < parseFloat(StartCommission)) {
			error = true;
			errormsg = '结束佣金比例必须大于起始佣金比例';
		}
		 else if (parseFloat(EndCommission) >100) {
				error = true;
				errormsg = '佣金比例为0到100的整数！';
			}
		else if(parseFloat(EndCommission)<=0){
			error = true;
			errormsg = '佣金比例为0到100的整数！';
		}
	}
	if (error == true) {
		layer.msg(errormsg,{icon: 2});
		return false;
	}
	$('#doaminserach').submit();
}
function check_form_shop() {
	var errormsg = '';
	var error = false;
	var startGoodRating = $('input[name="startGoodRating"]').val();
	var endGoodRating = $('input[name="endGoodRating"]').val();
	var StartCommission =$('input[name="StartCommission"]').val();
	var EndCommission = $('input[name="EndCommission"]').val();
	var startCredit =$('input[name="startCredit"]').val();
	var endCredit = $('input[name="endCredit"]').val();
	if (startGoodRating.length>0) {
		if (isNaN(startGoodRating)) {
			error = true;
			errormsg = '起始好评率必须为数字';
		} else if (startGoodRating < 0) {
			$('input[name="startGoodRating"]').val('0');
			error = true;
			errormsg = '起始好评率必须大于0';
		}
	}
	if (endGoodRating.length>0) {
		if (isNaN(endGoodRating)) {
			error = true;
			errormsg = '结束好评率必须为数字';
		} else if (parseFloat(endGoodRating) < parseFloat(startGoodRating)) {
			error = true;
			errormsg = '结束好评率必须大于起始好评率';
		}
		else if(parseFloat(endGoodRating)==0){
			error = true;
			errormsg = '结束好评率必须大于0!';
		}
	}
	if (StartCommission.length>0) {
		if(!(/^\d+$/.test(StartCommission))){
			error = true;
			errormsg = '起始佣金比例必须为整数';
		}
		if (isNaN(StartCommission)) {
			error = true;
			errormsg = '佣金比例必须为数字';
		} else if (StartCommission < 0) {
			$('input[name="StartCommission"]').val('0');
			error = true;
			errormsg = '佣金比例必须为0到100的整数！';
		}
	}
	if (EndCommission.length>0) {
		if(!(/^\d+$/.test(EndCommission))){
			error = true;
			errormsg = '结束佣金比例必须为整数';
		}
		if (isNaN(EndCommission)) {
			error = true;
			errormsg = '佣金比例必须为数字';
		} else if (parseFloat(EndCommission) < parseFloat(StartCommission)) {
			error = true;
			errormsg = '结束佣金比例必须大于起始佣金比例';
		}
		 else if (parseFloat(EndCommission) >100) {
				error = true;
				errormsg = '佣金比例为0到100的整数！';
			}
		else if(parseFloat(EndCommission)<=0){
			error = true;
			errormsg = '佣金比例为0到100的整数！';
		}
	}
	if (startCredit.length>0) {
		if (isNaN(startCredit)) {
			error = true;
			errormsg = '起始信用例必须为数字';
		} else if (startCredit < 0) {
			$('input[name="startCredit"]').val('0');
			error = true;
			errormsg = '起始信用必须大于0';
		}
	}
	if (endCredit.length>0) {
		if (isNaN(endCredit)) {
			error = true;
			errormsg = '结束信用必须为数字';
		} else if (parseFloat(endCredit) < parseFloat(startCredit)) {
			error = true;
			errormsg = '结束信用必须大于起始信用';
		}
		else if(parseFloat(endCredit)==0){
			error = true;
			errormsg = '结束信用必须大于0!';
		}
	}
	if (error == true) {
		layer.msg(errormsg,{icon: 2});
		return false;
	}
	$('#shopserach').submit();

}
function check_spreaddoamin() {
	$('#spreaddoamin').submit();
}
function check_spreadshop() {
	$('#spreadshop').submit();
}