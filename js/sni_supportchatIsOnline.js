/**** check if the supportChat is online ****/

function initOnlineCheck(url) {
    if(checkPids) {
		AjaxChatCheck.checkIfOnline(checkPids,url);
	}	
}

var AjaxChatCheck = {
	timer:'',
	checkIfOnline:function(pids,url) {
		this.timer = new Ajax.PeriodicalUpdater(
			"",
			url+"&cmd=checkIfOnline&chatPids="+pids, {
				method:'get',
				onSuccess:function(r) {
					online = r.responseXML;
					var els = online.getElementsByTagName("numIndex");
					for(var i=0; i<els.length; i++) {
						var chatUid = els[i].getAttribute("index");
						var isOnline = els[i].childNodes[0].nodeValue;
	                    onlineChat = $("tx_snisupportchat_pi1_onlineLogo_"+chatUid);
    	                offlineChat = $("tx_snisupportchat_pi1_offlineLogo_"+chatUid);
						if(isOnline == 1 && onlineChat.className == "hidden") {
	                        offlineChat.className = "hidden";
    	                    offlineChat.style.display = "none";
        	                onlineChat.className = "";
            	            onlineChat.style.display = "inline";
						}
						else {
                        	if(isOnline==0 && offlineChat.className == "hidden") {
	                            onlineChat.className = "hidden";
    	                        onlineChat.style.display = "none";
        	                    offlineChat.className = "";
            	                offlineChat.style.display = "inline";
							}
						}
					}
				},
				frequency: globFreq
			}
		);
	},
	logout:function() {
		if(this.timer) this.timer.stop;
	}
}

function sniSupportchatOpenWindow(url,winName,winParams) {
	var sniTheWindow = window.open(url,winName,winParams);
	if (sniTheWindow) {
		sniTheWindow.focus();
	}
}

