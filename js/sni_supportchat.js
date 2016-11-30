/**** the support chat ****/
				
function initChat(eidUrl) {
	checkJs();
	initAjaxChat(eidUrl); 
}

function checkJs() {
	$("chaterror").setStyle("display","none");
	$("chatboxouter").setStyle("display","block");
}

function initAjaxChat(eidUrl) {
	//define chat globally so that chatDestroy() can be performed without moocode
	/** tradem Pass useTypingIndicator as constructor parameter */
	chat = new AjaxChat(globPid,globLang,globFreqMessages,timeFormated,useTypingIndicator,{"eidUrl": eidUrl});
	chat.createChat();
}

var AjaxChat = new Class({
	"Implements": Options,
	"options": {
		"id": {	
			"textBox": "sniTextbox",
			"sendButton": "sniSendMessage",
			"closeButton": "sniChatClose",
			"headline": "chatboxTitle",
			"chatbox": "snisupportchatbox"
		}
	},
	"initialize": function(pid,lang,freq,time,useTypingIndicator,options) {
		this.setOptions(options);
		this.pid = pid;
		this.lang = lang;
		this.freq = freq; 
		this.strftime = time;
		this.msgToSend = Array(); // storage for messages to send at the next request
		this.inactive = 0;
		/** tradem 2012-04-11 Used to control notification & display of typing indicator */
		this.useTypingIndicator = useTypingIndicator; 
		this.msgToSend = Array(); // storage for messages to send at the next request
		this.inactive = 0;
		
		this.scrollDownAni = new Fx.Scroll(this.options.id.chatbox, {
            "duration": "short",
            "link": "cancel"
        });
		$(this.options.id.textBox).focus();
        
		/** tradem 2012-04-03: Added */
		this.typingStatus = 0;  // flag that represents the current typing status
	},
	"createChat": function() {
		/* the init function for creating a new chat */
		/** tradem 2012-04-01 */
		/** tradem 2012-04-13 Added useTypingIndicator*/
		new Request({
			"url": this.options.eidUrl+"&cmd=createChat&pid="+this.pid+"&L="+this.lang+"&useTypingIndicator="+this.useTypingIndicator,
			"method": "get",
			"onComplete": function(respText) {
				this.uid = respText;
				if(this.uid.toInt()) {
					// the chat was created succesfully !
					// write system Welcome message
					this.insertMessage(diffLang.chatWelcome,"system",diffLang.system,this.strftime);
					// create the unique Request Object 
					this.request = new Request({
						"link": "chain", // should never be chained, just to be sure..
						"url": this.options.eidUrl+"&pid="+this.pid+"&chat="+this.uid+"&useTypingIndicator="+this.useTypingIndicator,
						"onComplete": this.requestDone.bind(this)
					});
					// call the getMessages function periodically
					this.timer = this.getAll.delay(this.freq,this);
					// create the button events
					this.addEvents();
				}
				else {
					this.insertMessage("The chat could not be created! Please inform the site admin.","system",diffLang.system,this.strftime);
				}
			}.bind(this)
		}).send(); 
	},
    "createMessage": function(e) {
        /* called if a new message was posted */
        var message = $(this.options.id.textBox).get("value");
        if(message) {
			this.msgToSend.include(message); // gather it in array for next request
			this.insertMessage(message.stripScripts(),"feuser",diffLang.chatUsername,this.strftime); // insert it locally (now)
            $(this.options.id.textBox).set("value","");
        }
        $(this.options.id.textBox).focus();
        e.stop(); // prevent standart events for onEnterButton
    },
	"getAll": function() {
		/* this function is called with a delay by itself, get all messages or rather post new messages to/from Server */
		var postMessages = "";
		this.msgToSend.each(function(item,index) {
			if(item) {
				postMessages +="&msgToSend["+index+"]="+encodeURIComponent(item);
			}	
		}.bind(this));
		if(postMessages) {
			postMessages += "&chatUsername="+diffLang.chatUsername;
		}
		this.msgToSend.empty(); // clean the array of post messages
		/** tradem 2012-04-13 Added useTypingIndicator*/
		this.request.send({
			"data": "cmd=getAll"+"&useTypingIndicator="+this.useTypingIndicator+"&lastRow="+this.lastRow + postMessages + "&isTyping=" + this.typingStatus,
			"method": "post"
		});	
	},
	"requestDone": function(respText,respXML) {
		/* the onComplete function of the unique AJAX Request */
		if(respXML) {
			var root = respXML.getElementsByTagName("phparray");
			if(root) {
				this.strftime = root[0].childNodes[0].firstChild.nodeValue; // update Time
				if(root[0].childNodes[1].nodeName=="status") {
					/* no access to the chat, show message why ,remove Events and stop polling */
					switch(root[0].childNodes[1].firstChild.nodeValue) {
						case 'timeout':
							this.insertMessage(diffLang.chatTimeout,"system",diffLang.system,this.strftime);
						break;
						case 'be_user_destroyed':
							this.insertMessage(diffLang.chatDestroyedByAdmin,"system",diffLang.system,this.strftime);
						break;
						case 'no_access':
							this.insertMessage(diffLang.chatNoAccess,"system",diffLang.system,this.strftime);
						break;
					}
					this.removeEvents();
					this.inactive = 1;
				}
				else {
					this.lastRow = root[0].childNodes[1].firstChild.nodeValue; // update last Row
                    if (this.useTypingIndicator == 1) {                    
                        var status = root[0].childNodes[3].firstChild.nodeValue; // get Status (is Typing ecc.)
                        if(status == 1) {
                            $('typingPen').setStyle("display", "inline");                            
                        }
                        else {
                            $('typingPen').setStyle("display", "none");                        
                        }
                    }    
					if(root[0].childNodes[2]) {
						var messages = root[0].childNodes[2].getElementsByTagName("numIndex");
						if(messages.length>0) {
							for(var i=0; i<messages.length; i++) {
								var date = messages[i].childNodes[0].firstChild.nodeValue;
								var code = messages[i].childNodes[1].firstChild.nodeValue;
								var name = messages[i].childNodes[2].firstChild.nodeValue;
								var message = messages[i].childNodes[3].firstChild.nodeValue;
								this.insertMessage(message,code,name,date);
							}
						}
					}
				}	
			}
		}
		if(!this.inactive) {
			// call the get Messages function with delay
			$clear(this.timer);
			this.timer = this.getAll.delay(this.freq,this);
		}
	},
	str_replace: function(search, replace, subject) {
	    return subject.split(search).join(replace);
	},
	"insertMessage": function(message,code,name,time) {
		/* inserts a Message in the Chatbox (HTML) and scrolls the texbox */
		if(code!= "system") {
			$each(sniSupportChatSmilies,function(img,key) {
				var theImg = '<img src="typo3conf/ext/sni_supportchat/pics/smiley/'+img+'" />';
				message = this.str_replace(key,theImg,message);
			}.bind(this)); 
		}	
		if(code!="title") {
			var user = "";
			switch (code) {
				case 'system':
					user = '<span class="system-message">'+name+' > </span>';
				break;
				case 'beuser':
					user = '<span class="supportler-message">'+name+' > </span>';
				break;
				default:
					// fe-user message
					user = '<span>'+name+' > </span>';
			}
			var allWrap = '<span class="date">'+time+' > </span> '+user;
			var msgEl = new Element("span",{
				"class": "message",
				"html": message
			});
			var newLine = new Element("p",{
				"html": allWrap
			});
			newLine.adopt(msgEl);
			$(this.options.id.chatbox).adopt(newLine);
			this.scrollTextbox.delay(100,this);
		}
		else {
			// insert a new title in top of the chatbox
			$(this.options.id.headline).set("text",message);
		}
	},
	"scrollTextbox": function() {
		this.scrollDownAni.toBottom();
	},
	"addEvents": function() {
		// insertMessage Button, close chat button,textarea onPressEnter
		$(this.options.id.sendButton).addEvent("click", this.createMessage.bind(this));
		$(this.options.id.textBox).addEvents({
			"enterButtonDown": this.createMessage.bind(this)
		});
		/** tradem 2012-04-11 Register event 'isTyping' only if typing indicator has been configured. */
		if (this.useTypingIndicator == 1) {
			/** tradem: 2012-04-03: Register IsTyping Event */
			$(this.options.id.textBox).addEvents({				
			   "isTyping": this.setTypingState.bind(this)
			});
		}
		$(this.options.id.closeButton).addEvent("click", function(){
            this.destroyChat();
	        close_button_flag = true;
        }.bind(this));
        if($('exportButton')) {
            $('exportButton').addEvent("click", function(){
                $('data1').value=$('snisupportchatbox').get('html');
            });
        }    
	},	
	/** tradem 2012-04-03: Resets the Typing state */
	"resetTypingState": function() {					                
	     $clear(this.resetTimer);        
	     this.typingStatus = 0;
	},
	/** tradem 2012-04-03: Sets the Typing state, whenever a Keydown event has been fired.*/
	"setTypingState": function() {					                
         $clear(this.resetTimer);        
	     this.typingStatus = 1;
         this.resetTimer = this.resetTypingState.delay(this.freq+500, this);
	},
	"removeEvents": function() {
		$(this.options.id.sendButton).removeEvents();
		$(this.options.id.textBox).removeEvents();
		$(this.options.id.closeButton).removeEvents();
	},
	"destroyChat": function() {
		$clear(this.timer);
		this.inactive = 1;
		// write system chat destroyed message
        this.insertMessage(diffLang.systemByeBye,"system",diffLang.system,this.strftime);
		this.removeEvents();
		/** tradem 2012-04-13:  Pass useTypingIndicator now */		
		new Request({
			"url": this.options.eidUrl+"&cmd=destroyChat&chat="+this.uid+"&pid="+this.pid+"&useTypingIndicator="+this.useTypingIndicator,
			"method": "get",
			"onComplete": function() {
				window.close();
			}
		}).send();	
		
	}
});
	
Element.Events.enterButtonDown = {
    base: 'keypress', //we set a base type
    condition: function(event){ //and a function to perform additional checks.
        if(event.key == "enter") {
            return (true);
        }
        else {
            return (false);
        }
    }
};
/** tradem: 2012-04-03: Added IsTyping Event */
Element.Events.isTyping = {
    base: 'keypress', //we set a base type
    condition: function(event){ //and a function to perform additional checks.
        if(event.key == "enter") {
            return (false);
        }
        else {
            return (true);
        }
    }
};

