// called on domready, create the chat
function initChat(freq, useTypingIndicator) {
	startBackendChat(freq ,useTypingIndicator);
}

function startBackendChat(freq ,useTypingIndicator) {
	//define chat globally so that chatDestroy() can be performed without moocode
	/** tradem Pass useTypingIndicator as constructor parameter */
	chat = new ChatBackend(
			freq,
			useTypingIndicator
	);
	chat.initChat();
}

/**
 * Manager class for all chats
 */
class ChatBackend {

	/**
	 * Constructor
	 *
	 * @constructor
	 * @param {int} freq	Frequency of loading chat
	 * @param {boolean} useTypingIndicator	Boolean of typing feature
	 *
	 * @return void
	 */
	constructor(freq, useTypingIndicator) {
		this.freq = freq; // the period for the request
		this.useTypingIndicator = useTypingIndicator; // is typing status
		this.chatsList = new Array(); // Array to gather the Chat Objects, key=the chat uid, value = the chat object
		this.lastRowQuery = ""; // the last row GetVar Array: key => chatUid, value => lastRow
		this.msgToSendQuery = "";
		this.lockChatQuery = "";
		this.destroyChatQuery = "";
		this.typingStatusQuery = "";
		this.timer = null;
		this.lastLogRow = 0; // the last log row uid
		this.logMsgObj = new LogMessage();
		this.backendUserStorage = new BackendUser(); // the current logged in be-users
		this.isFirstChat = true; // initialize by first open chat
	}

	/**
	 * Initialize backend chat
	 */
	initChat() {
		// Avoid to multiply timer
		this.removeTimer(this.timer);
		// Collect all changed variables and get it for POST request
		this.getChatsPostData();
		fetch(TYPO3.settings.ajaxUrls['chat_response'],{
			link: "chain",
			method: 'POST',
			cache: 'no-cache',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded'
			},
			body: "cmd=doAll" + this.lastRowQuery
					+ "&lastLogRow=" + this.lastLogRow
					+ this.msgToSendQuery + this.lockChatQuery
					+ this.destroyChatQuery + this.typingStatusQuery
		})
		.then((res) => {
			if (!res.ok) {
				throw new Error(
						"HTTP exception at response in ChatBackend.initChat() with status: " + res.status);
			}
			return res.json();
		})
		.then((data) => {
			if (data.length !== 0) {
				this.processResponse(data);
			}
		});
	}

	/**
	 * Return all chat messages
	 *
	 * @param {json} data response
	 */
	processResponse(data) {
		/* the onComplete function of the unique AJAX Request */
		if (data.hasOwnProperty('fromDoAll')) {
			// Global variable strftime
			strftime = (data.fromDoAll.time)
					? data.fromDoAll.time : "";
			// Get all backend user
			this.backendUserStorage.resetUpdate();
			if (data.fromDoAll.hasOwnProperty('beUsers') && data.fromDoAll.beUsers.length > 0) {
				for (let i = 0; i < data.fromDoAll.beUsers.length; i++) {
					if (data.fromDoAll.beUsers[i].uid && data.fromDoAll.beUsers[i].name)
					this.backendUserStorage.addUser(
						data.fromDoAll.beUsers[i].uid,
						data.fromDoAll.beUsers[i].name
					);
				}
			} else {
				this.backendUserStorage.empty();
			}
			// Get all chats
			let isFirstChat = true
			if (data.fromDoAll.hasOwnProperty('chats') && data.fromDoAll.chats.length > 0) {
				data.fromDoAll.chats.forEach(function(item) {
					let uid = item.uid;
					let theChat = this.getChat(uid) || false;
					if (theChat) {
						theChat.updateLastRow(item.lastRow);
						theChat.updateBackendUsers(this.backendUserStorage);
						theChat.updateTypingStatus(item.type_status);
						// Alternatively if no data.fromDoAll.beUsers create an chat object
					} else {
						// create a chat object
						let crdate = item.crdate;
						let frontend_language = item.fe_language;
						let surfer_ip = item.surfer_ip;
						let be_user = item.be_user;
						let lastRow = item.lastRow;
						let lImg = item.language_flag;
						let lLabel = item.language_label;
						let additionalInfo =
								(item.additionalInfo && item.additionalInfo[0] !== "")
										? item.additionalInfo[0] : "";
						theChat = new Chats(
								uid,
								this.useTypingIndicator,
								crdate,
								frontend_language,
								be_user,
								lastRow,
								surfer_ip,
								lImg,
								lLabel,
								additionalInfo
						);
						// former this.chatsList.include(theChat);
						if (this.chatsList.indexOf(theChat) === -1) {
							this.chatsList.push(theChat)
						}
						theChat.updateBackendUsers(this.backendUserStorage, 1);
					} // end else
					// Handle messages
					if (item.messages && item.messages.length > 0) {
						item.messages.forEach(function(msgItem) {
								let msgCrdate = msgItem.crdate;
								let msgCode = msgItem.code;
								let msgName = msgItem.name;
								let msgMessage = msgItem.message;
								let msg = new Message(
										msgCrdate,
										msgCode,
										msgName,
										msgMessage
								);
								theChat.addMessage(msg);
						}.bind(this));
					}
					// Draw the chat (messages or whole chat)
					theChat.draw();
					// Cursor focus on first chat window
					if (this.isFirstChat === true) {
						theChat.addFocus();
						this.isFirstChat = false;
					}

					// Check if response claims to lock the chat
					if (item.from_lock_chat != null) {
						theChat.removeLoadingImg();
						theChat.lockChatDisplay(
								(item.from_lock_chat == 1) ? true : false
						);
					}
					// Check if response claims to destroy the chat
					if (item.hasOwnProperty('from_destroy_chat') && item.from_destroy_chat == 1) {
						this.removeChat(theChat);
					}
				}.bind(this));
			}
			// Adds log messages to backend
			if(data.fromDoAll.log) {
				if (data.fromDoAll.log.length > 0) {
					// Clear first log box
					this.logMsgObj.removeLogNodes();
					data.fromDoAll.log.forEach(function(logItem) {
						this.logMsgObj.addLogMessage(logItem.crdate, logItem.message);
					}.bind(this));
				}
			}
			this.lastLogRow = data.fromDoAll.lastLogRow;

			// Check if chats should be removed
			// For example another backend user has locked one chat
			if (data.fromDoAll.hasOwnProperty("chats")) {
				this.chatsList.forEach(function(item) {
					if (item.hasOwnProperty('uid')) {
						let toDelete = true;
						for (let i = 0; i < data.fromDoAll.chats.length; i++) {
							// Comparison of response of chats with current chat list
							if (item.uid == data.fromDoAll.chats[i].uid) {
								toDelete = false;
							}
						}
						if (toDelete) {
							this.removeChat(item);
						}
					}
				}.bind(this));
			}
			// Call request periodically to get changes of front- and backend
			this.getRequestInterval(this.freq);

		} else if (data.hasOwnProperty('fromNoAccess')) {
				alert(
						"You don't have access to this module, please try to re-login" +
						"in typo3 or contact your sys-admin!"
				);
		} else {
				//document.getElement("body").empty();
				//document.getElement("body").set("html",respText);
		}
	}

	/**
	 * Get chat reference to the chat with uid
	 *
	 * @param {int} uid
	 *
	 * @returns {(Object|null)} chatReference
	 */
	getChat(uid) {
		let chatReference = null;
		this.chatsList.forEach(function(item) {
			if(item.uid == uid) {
				chatReference = item;
			}
		});
		return chatReference;
	}

	/**
	 * Create all postVars from arrays for the next request (send messages,
	 * lock/unlock chats, destroy chats) and empty the arrays
	 */
	getChatsPostData() {
		this.lastRowQuery = "";
		this.msgToSendQuery = "";
		this.lockChatQuery = "";
		this.destroyChatQuery = "";
		this.typingStatusQuery = "";
		this.chatsList.forEach(function (item, index) {
  		// last Row
			if (item.lastRow) {
				this.lastRowQuery += "&lastRowArray[" + item.uid + "]=" + item.lastRow;
			}
			// send messages
			item.msgToSend.forEach(function(itemM, indexM) {
				if (itemM != "") {
					this.msgToSendQuery += "&msgToSend[" + item.uid + "][" + indexM + "]="
							+ encodeURIComponent(itemM);
				}
			}.bind(this));
			// delete the msgToSend Array
			item.msgToSend.length = 0;
			// lock / unlock Chat
			if (!!(item.typingStatus||item.typingStatus === 0)) {
				this.typingStatusQuery += "&typingStatus["+item.uid+"]="+item.typingStatus;
			}
			if (!!(item.lockReq||item.lockReq === 0)) {
				this.lockChatQuery += "&lockChat["+item.uid+"]="+item.lockReq;
				item.lockReq = null;
			}
			// destroy Chat
			if (item.destroyReq) {
				this.destroyChatQuery += "&destroyChat["+item.uid+"]=1";
			}
			item.destroyReq = 0;
		}.bind(this));
	}

	/**
	 * Periodic caller of initChat()
	 *
	 * @param int frequency
	 */
	getRequestInterval(frequency) {
		this.timer = setInterval(
				this.initChat.bind(this),
				frequency || 1000
		);
	}

	/**
	 * Get text node
	 *
	 * @param node
	 *
	 * @returns {string}
	 * @deprecated
	 */
	getTextNode(node) {
		/* just to get node.nodeValue */
		if(node) {
			return (node.nodeValue);
		} else {
			return "";
		}
	}

	/**
	 * Removes a chat
	 *
	 * @param {Chats} chatObj	Chat object of class Chat
	 */
	removeChat(chatObj) {
		chatObj.fadeOut(
				document.getElementById(chatObj.idChatboxWrap),
				3000
		);
		// Remove chatObj from this.chats list
		for (let i = this.chatsList.length; i--;) {
			if (this.chatsList[i] === chatObj) {
				this.chatsList.splice(i, 1);
			}
		}

	}

	/**
	 * Remove timer
	 *
	 * @param timer
	 *
	 * @returns {null}
	 */
	removeTimer(timer) {
		window.clearTimeout(timer);
		window.clearInterval(timer);
		timer = null;
		return null;
	}
}

/**
 * Chats class
 *
 */
class Chats
{
	/**
	 * Constructor
	 *
	 * @constructor
	 * @param uid
	 * @param useTypingIndicator
	 * @param crdate
	 * @param language
	 * @param be_user
	 * @param lastRow
	 * @param surferIp
	 * @param lImg
	 * @param lLabel
	 * @param additionalInfo
	 *
	 * @return void
	 */
	constructor(
			uid,
			useTypingIndicator,
			crdate,
			language,
			be_user,
			lastRow,
			surferIp,
			lImg,
			lLabel,
			additionalInfo
	) {
		this.uid = uid;
		this.useTypingIndicator = useTypingIndicator;
		this.crdate = crdate;
		this.language = language;
		this.lastRow = lastRow;
		this.surferIp = surferIp;
		this.lImg = lImg;
		this.lLabel = lLabel;
		this.additionalInfo = additionalInfo;
		// Internal global variables
		this.chatVisible = false;
		this.messagesToDraw = Array(); // Array for messages to draw
		this.lockReq = null; // if set to 0 or 1 then the chat will send at the next request the lock / unlock request
		this.destroyReq = 0;
		this.msgToSend = Array();
		this.typingStatus = 0;
		this.chatLocked = (be_user) ? 1 : 0;
		this.hotkeyFixText = Array(); // fixText for Hotkeys [0] = text for Alt+0
		this.delay = 50; // Frequency of scrolling text in message window to latest entry
		// Define ids for single chat window
		this.setHtmlIds();
		// Play alert sound for every new chat
		this.playAlertSound();
	}

	/**
	 * Adds all necessary events for a single chatbox
	 */
	addEvents() {
		/* Register event to add new message */
		document.getElementById(this.idTextarea).addEventListener(
				"keyup",
				function(event) {
					// ENTER key with code 13 is pressed
					if (event.key === "Enter") {
						this.createMessage(event);
					}
					// ALT key with code 18 is pressed
					else if (event.code == 18) { // ALT key has code 18
						document.getElementById(this.idFixTextUl).listClass.remove("visible");
						document.getElementById(this.idFixTextUl).listClass.add("invisible");
					// Otherwise show that someone is currently typing
					} else {
						if (this.useTypingIndicator == 1) {
							event.preventDefault();
							this.setTypingState();
						}
					}
				}.bind(this)
		);
		// Register event to enter predefined text sample with ALT + {number}
		document.getElementById(this.idTextarea).addEventListener(
			"keydown",
			function(event) {
				if (event.altKey) {
					event.preventDefault();
					// Alt keypressed
					if (event.key > 0 && event.key < 9) {
						if (this.hotkeyFixText[event.key]) {
							this.addFixText(this.hotkeyFixText[event.key]);
						}
					}
					event.stopPropagation();
				}
			}.bind(this)
		);
		// Register event of click button to destroy chat
		document.getElementById(this.idClose).addEventListener(
				"click",
				this.destroyChat.bind(this)
		);
		// Register event of click button to lock chat
		document.getElementById(this.idAssumeIcon).addEventListener(
				"click",
				this.lockChat.bind(this)
		)
		document.getElementById(this.idLock).addEventListener(
				"click",
				this.lockChat.bind(this)
		);
	}

	/**
	 * Add fix text - inserts the text at the current cursor position in
	 * textarea of current chatbox
	 *
	 * @param {string} text
	 */
	addFixText(text) {
		this.insertAtCursor(
				document.getElementById(this.idTextarea),
				text
		);
	}

	/**
	 * Creates the ul navigation for fix texts from global var fixText,
	 * only called by draw (create whole chat)
	 *
	 * @returns {Object} ul
	 */
	addFixTextMenu() {
		let ul = document.createElement("ul");
			ul.id = this.idFixTextUl;
			ul.className = "text-snippets"

		// Variable fixText seems not used in any context
		if (fixText[this.language]) {
			let count = 1;
			for (let i in fixText[this.language]) {
				let li = document.createElement("li");
					li.addEventListener(
							"click",
							function() {
								this.addFixText(fixText[this.language][i]);
							}.bind(this)
					);
				if (count < 10) {
					li.innerText = (fixText[this.language][i]+" [Alt+"+count+"]");
					this.hotkeyFixText[count] = fixText[this.language][i];
				}	else {
					li.innerText = fixText[this.language][i];
				}
				count ++;
				ul.appendChild(li);
			} // end for
		}	else {
			let li = document.createElement("li");
				li.innerText = LL.noFixTextInThisLanguage;
				li.className = "unclickable"
			ul.appendChild(li);
		} // end else
		// Deprecated tag class="last" replaced by css :last-child
		//ul.children[ul.children.length - 1].classList.add("last");
		return ul;
	}

	/**
	 * Adds cursor focus to textarea field
	 */
	addFocus() {
		document.getElementById(this.idTextarea).focus();
	}

	/**
	 * Adds message html code in the messages array which will be displayed
	 * on the next draw() call
	 *
	 * @param {Object} msg
	 *
	 * @returns {Array}
	 */
	addMessage(msg) {
		//this.messagesToDraw.include(msg.getNode());
		//if (this.messagesToDraw.indexOf(msg.getNode()) != -1) {
			return this.messagesToDraw.push(msg.getNode())
		//}
	}

	/**
	 * Clear timer - used by typing status
	 *
	 * @param timer
	 * @returns {null}
	 */
	clearTimer(timer) {
		window.clearTimeout(timer);
		window.clearInterval(timer);
		timer = null;
		return null;
	}

	/**
	 * Create message and assemble messages in msgToSend array
	 *
	 * @param event
	 */
	createMessage(event) {
		let msg = document.getElementById(this.idTextarea).value;
		if (msg) {
			let msgObj = new Message(
					strftime,
					"beuser",
					LL.username,
					this.stripScripts(msg)
			);
			// Insert message at DOM
			document.getElementById(this.idChatbox).appendChild(msgObj.getNode());
			// Scroll text messages to latest
			this.scrollTextBox(this.idChatbox);
			if (this.msgToSend.indexOf(msgObj.getNode()) === -1) {
				this.msgToSend.push(msg.trim())
			}
			document.getElementById(this.idTextarea).value = "";
		}
		document.getElementById(this.idTextarea).focus();
		event.stopPropagation();
	}

	/**
	 *  Close single chat window
	 */
	destroyChat() {
		// Write system message that chat will be closed
		var msgObj = new Message(
				strftime,
				"system",
				LL.system,
				LL.chatDestroyedMsg
		);
		this.addLoadingImg();
		document.getElementById(this.idChatbox).appendChild(msgObj.getNode());
		this.scrollTextBoxObj(this.idChatbox);
		//document.getElementById(this.idTextarea).removeEventListener();
		document.getElementById(this.idClose).removeEventListener(
				'click',
				this.destroyChat
		);
		this.destroyReq = 1;
	}


	/**
	 * Draws the chat if the chat isn't visible then draw the whole chat, else
	 * draw only messages ecc.
	 */
	draw() {
		if (!this.chatVisible) {
			// draw the whole chat
			let wrap = document.createElement("div");
				wrap.id = this.idChatboxWrap;
				wrap.className = "chat_single_wrap";
			let topNavDiv = document.createElement("nav");
				topNavDiv.className = "top";
			let close = document.createElement("a");
				close.id = this.idClose;
				close.className = "chat_close";
				close.innerHTML = "&nbsp";
			let ul = document.createElement("ul");
				ul.id = this.idNaviTop;

			// List one - navigation for all current logged in be-users
			let li1 = document.createElement("li");
				li1.innerText = LL.options;
			let li1Ul = document.createElement("ul");
			let li1UlLi1 = document.createElement("li");
				li1UlLi1.id = this.idLock;
				li1UlLi1.innerText = LL.options_lock;
			let li1UlLi2 = document.createElement("li");
				li1UlLi2.id = this.idAssume;
				li1UlLi2.className = "last";
				li1UlLi2.innerText = LL.options_assume;

			if (this.beUserSelectNode) {
				li1UlLi2.append(this.beUserSelectNode);
				this.beUserSelectNode = null;
			}
			li1Ul.append(li1UlLi1, li1UlLi2);
			li1.append(li1Ul);

			// List two
			let li2 = document.createElement("li");
				li2.innerText = LL.text_pieces;
			let li2Ul = document.createElement("ul");
				li2.appendChild(this.addFixTextMenu());

			// Assemble all of top navigation
			ul.append(li1, li2);
			topNavDiv.append(ul);
			let lang = (this.lImg || this.lLabel) ? this.lImg : this.language;
			let title = document.createElement("div");
				title.className = "infobox";
				title.innerHTML = "<p>Chatbox: ID " + this.uid + ", " + LL.created_at
						+ " " +	this.crdate + "</p><p>Client: " + this.surferIp + ", "
						+	LL.language +	": " + lang + " " + this.additionalInfo + "</p>"
			let chatbox = document.createElement("div");
				chatbox.id = this.idChatbox;
				chatbox.className = "single_chatbox";

			// Adds welcome message to every new opened window
			let welcomeMsg = new Message(
					strftime,
					"system",
					LL.system,
					LL.welcomeMsg
			);
			this.addMessage(welcomeMsg);

			// Append the messages to the chatbox
			if (this.messagesToDraw.length > 0) {
				this.messagesToDraw.forEach(function(item) {
					chatbox.appendChild(item);
				});
			}

			let textBoxLabel = document.createElement("p");
				textBoxLabel.className = "descr";
				textBoxLabel.innerText = LL.type_youre_message;
			let textArea = document.createElement("textarea");
				textArea.id = this.idTextarea;
			let assumeIcon = document.createElement("a");
				assumeIcon.id = this.idAssumeIcon;
				assumeIcon.className = "status_icon lock_it";
				assumeIcon.innerHTML = "&nbsp;"
			let typingIcon = document.createElement("img");
				typingIcon.id = this.idTypingIcon;
				typingIcon.className = "not_typing";
				typingIcon.src = assetsPath + "Images/icon-typing.svg";
			let status = document.createElement("p");
				status.id = this.idStatus;
				status.className = "chatbox_status";
				status.innerText = LL.status_unlocked;
			wrap.append(close, topNavDiv, title, chatbox, textBoxLabel, textArea, assumeIcon,
					typingIcon, status);

			// Insert in html with fade in effect - opacity
			wrap.style.opacity = 0;
			document.getElementById(this.idChatboxOuter).append(wrap);
			this.fadeIn(
					document.getElementById(this.idChatboxWrap),
					3000
			);

			this.chatVisible = true;
			// If the chat is locked, change status and icon
			if (this.chatLocked) {
				this.lockChatDisplay(true);
			}
			// Scroll chat window to bottom
			this.scrollTextBox(this.idChatbox);
			// Add events
			this.addEvents();
		} else { // end if - if chat not visible
			// Display new messages and joined backend-users
			if (this.messagesToDraw.length > 0) {
				// Append the messages to the chatbox
				for (let i=0; i< this.messagesToDraw.length; i++) {
					document.getElementById(this.idChatbox)
						.appendChild(this.messagesToDraw[i]);
				}
				// Play alert sound on every message
				this.playAlertSound();
				this.scrollTextBox(this.idChatbox);
			}
			if (this.beUserSelectNode) {
				// delete the old select Menu and adopt the new one
				//if ($(this.idAssume).getElement("ul")) {
				//	$(this.idAssume).getElement("ul").dispose();
				//}
				document.getElementById(this.idAssume)
					.appendChild(this.beUserSelectNode);
				// We have changed the html, delete the node
				this.beUserSelectNode = null;
				// Re-create navigation
				delete(this.naviMenu);
			}
		}
		// Clear the messages variable
		this.messagesToDraw.length = 0;
	}

	/**
	 * Fade in effect for element
	 * https://jsfiddle.net/TH2dn/606/
	 *
	 * @param el
	 * @param {int} time
	 */
	fadeIn(el, time) {
		el.style.opacity = 0;
		let last = +new Date();
		let tick = function() {
			el.style.opacity = +el.style.opacity + (new Date() - last) / time;
			last = +new Date();
			if (+el.style.opacity < 1) {
				(window.requestAnimationFrame && requestAnimationFrame(tick)) || setTimeout(tick, 16);
			}
		};
		tick();
	}

	/**
	 * Fade out effect for element
	 *
	 * @param el
	 * @param {int} time
	 */
	fadeOut(el, time) {
		el.style.opacity = 1;
		let last = +new Date();
		let tick = function() {
			el.style.opacity = +el.style.opacity - (new Date() - last) / time;
			last = +new Date();
			if (+el.style.opacity > 0) {
				(window.requestAnimationFrame && requestAnimationFrame(tick)) || setTimeout(tick, 16);
			} else {
				el.remove();
			}
		};
		tick();
	}

	/**
	 * Insert text at cursor position borrowed from
	 * https://jsfiddle.net/Znarkus/Z99mK/
	 *
	 * @param {Object} myField
	 * @param {string} myValue
	 *
	 */
	insertAtCursor(myField, myValue) {
		//IE support
		if (document.selection) {
			myField.focus();
			let sel = document.selection.createRange();
			sel.text = myValue;
		}
		//MOZILLA and others
		else if (myField.selectionStart || myField.selectionStart == '0') {
			let startPos = myField.selectionStart;
			let endPos = myField.selectionEnd;
			myField.value = myField.value.substring(0, startPos)
					+ myValue
					+ myField.value.substring(endPos, myField.value.length);
			myField.selectionStart = startPos + myValue.length;
			myField.selectionEnd = startPos + myValue.length;
		} else {
			myField.value += myValue;
		}
 	}

	/**
	 * Lock|unlock chat for the current backend user by displaying loading image
	 * during sending a request
	 */
	lockChat() {
		if (!this.loading) {
			this.addLoadingImg();
			if(!this.chatLocked) {
				// lock it
				this.lockReq = 1;
			} else {
				// unlock it
				this.lockReq = 0;
			}
		}
	}

	/**
	 * Lock chat id called if lock chat was completed by last request or if chat
	 * which was locked is now newly created, just set chatLocked variable and
	 * change symbols and status text
	 *
 	 * @param {boolean} hasLocked If true chat is locked if false than unlocked
	 */
	lockChatDisplay(hasLocked) {
		let assume = document.getElementById(this.idAssumeIcon);
		let stat = document.getElementById(this.idStatus);
		let lock = document.getElementById(this.idLock);

		if (hasLocked === true) {
			assume.classList.remove("lock_it");
			assume.classList.add("unlock_it");
			stat.innerText = LL.status_locked;
			lock.innerText = LL.options_unlock;
			this.chatLocked = true;
		}	else {
			assume.classList.remove("unlock_it");
			assume.classList.add("lock_it");
			stat.innerText = LL.status_unlocked;
			lock.innerText = LL.options_lock;
			this.chatLocked = false;
		}
	}

	/**
	 * Play alert sound
	 */
	playAlertSound() {
		if (document.getElementById("beep_alert")) {
			if (document.getElementById("alert_check").checked) {
				try {
					document.getElementById("beep_alert").play(4);
				} catch (e) {
					console.log('No alert sound played')
				}
			}
		}
	}

	/**
	 * Reset typing state
	 */
	resetTypingState() {
		this.clearTimer(this.resetTimer);
		this.typingStatus = 0;
	}

	/**
	 * Timer function of asking scroll text to bottom object in two steps. First
	 * in time and second always after a indicated period.
	 *
	 * @param id					Id element of DOM
	 */
	scrollTextBox(id) {
		this.scrollTextBoxObj(id);
		window.setTimeout(function periodic() {
			this.scrollTextBoxObj(id);
			window.setTimeout(periodic.bind(this), this.delay * 1000 || 1000)
		}.bind(this), this.delay * 1000 || 1000);
	}

	/**
	 * Scroll to bottom object
	 *
	 * @param id	Id of DOM Element
	 */
	scrollTextBoxObj(id) {
		let elem = document.getElementById(id);
		if (elem) {
			let shouldScroll = elem.scrollTop + elem.clientHeight ===
					elem.scrollHeight;
			if (!shouldScroll) {
				elem.scrollTop = elem.scrollHeight;
			}
		}
	}

   /**
	 * Set all HTML id for current chat window
	 */
	setHtmlIds() {
		this.idChatboxWrap = "chatBoxWrap_" + this.uid;
		this.idAssumeIcon = "assumeChat_" + this.uid;
		this.idTypingIcon = "typingIcon_" + this.uid;
		this.idChatbox = "chatBox_" + this.uid;
		this.idTextarea = "chatTextarea_" + this.uid;
		this.idChatboxOuter = "chatboxes_wrap";
		this.idClose = "chatClose_" + this.uid;
		this.idLock = "chatLock_" + this.uid;
		this.idAssume = "chatAssume_" + this.uid;
		this.idStatus = "chatStatus_" + this.uid;
		this.idNaviTop = "chatsNaviTop_" + this.uid;
		this.idFixTextUl = "chatsFixTextUl_" + this.uid;
		this.idAssumeOk = "popUpAssumeOk_" + this.uid;
		this.idAssumeAbort = "popUpAssumeAbort_" + this.uid;
	}

	/**
	 * Sets typing state, whenever a keydown event has been fired
	 */
	setTypingState() {
		this.clearTimer(this.resetTimer);
		this.typingStatus = 1;
		// this.resetTimer = this.resetTypingState.delay(this.freq + 500, this);
		this.resetTimer = setInterval(
				this.resetTypingState.bind(this),
				this.freq || 3000
		);
	}

	/**
	 * Strip <script> tags
	 *
	 * @param {string} text
	 *
	 * @returns {string} text
	 */
	stripScripts(text) {
		let script_regex = /<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi;
		while (script_regex.test(text)) {
			text = text.replace(script_regex, "");
		}
		return text;
	}

	/**
	 * Hand over a chat responsibility to another backend user
	 *
	 * @param {int} backendUserId
	 *
	 * @returns {null}
	 * @todo Method has to be implemented 
	 */
	transferChatToUser(backendUserId){}

	/**
	 * Updates backend users
	 *
	 * @param {Object} backendUserStorage
	 * @param {int} forceUpdate
	 */
	updateBackendUsers(backendUserStorage, forceUpdate = 0) {

		if (backendUserStorage.toUpdate() || forceUpdate) {
			let tmp = backendUserStorage.getUsers();
			let ul = document.createElement("ul");
			if(tmp.length > 0) {
				for (let i in tmp) {
					if (typeof(tmp[i])=="string") {
						var li = document.createElement("li");
							li.innerText = tmp[i];
							li.addEventListener(
								"click",
								this.transferChatToUser.bind(this,i)
							);
						ul.appendChild(li);
					}
				}
			}
			if (!li) {
				// Show that no backend user is currently online
				let li = document.createElement("li");
					li.className = "unclickable";
					li.innerText = LL.noBeUserOnline
				ul.appendChild(li);
			}
			// Get last element of list
			ul.children[ul.children.length - 1].classList.add("last");
			this.beUserSelectNode = ul;
		}
	}

	/**
	 * Updates last row
	 *
	 * @param {int} lastRow
	 */
	updateLastRow(lastRow) {
		this.lastRow = lastRow;
	}

	/**
	 * Updates if user just typing
	 *
	 * @param {boolean} status
	 */
	updateTypingStatus(status) {
		let elemUserIsTyping = document.getElementById(this.idTypingIcon);
		if (status === true) {
			if (elemUserIsTyping.classList.contains("not_typing")) {
				elemUserIsTyping.classList.add("typing");
				elemUserIsTyping.classList.remove("not_typing")
			}
		} else {
			if (elemUserIsTyping.classList.contains("typing")) {
				elemUserIsTyping.classList.add("not_typing");
				elemUserIsTyping.classList.remove("typing");
			}
		}
	}

	/**
	 * Helper class to display loading image
	 */
	addLoadingImg() {
		this.loading = 1;
		document.getElementById(this.idAssumeIcon).classList.add("loading");
	}

	/**
	 * Helper class to remove displaying loading image
	 */
	removeLoadingImg() {
		this.loading = 0;
		document.getElementById(this.idAssumeIcon).classList.remove("loading");
	}
}

/**
 * Class for backend user
 */
class BackendUser {

	/**
	 * Constructor
	 *
	 * @constructor
	 * @param backendUsers
	 * @param isChanged
	 */
	constructor(
			backendUsers,
			isChanged
	) {
		this.backendUsers = Array();
		this.isChanged = 0;
	}

	/**
	 * Set back $somethingChanged always to zero.
	 */
	resetUpdate() {
		this.isChanged = 0;
	}

	/**
	 * Adds an user
	 *
	 * @param {int} uid
	 * @param {string} name
	 */
	addUser(uid, name) {
		if (!this.backendUsers[uid]) {
			this.isChanged = 1;
			this.backendUsers[uid] = name;
		}
	}

	/**
	 * Get all users
	 *
	 * @return {array}
	 */
	getUsers() {
		return (this.backendUsers);
	}

	/**
	 * Get if something changes
	 *
	 * @returns {number}
	 */
	toUpdate() {
		return (this.isChanged);
	}

	/**
	 * Clean all users
	 */
	empty() {
		if (this.backendUsers.length > 0) {
			delete (this.backendUsers);
			this.backendUsers = Array();
			this.isChanged = 1;
		}
	}
}

/**
 * Class for log messages of support chat
 */
class LogMessage {

	/**
	 * Constructor
	 */
	constructor() {
		this.idLogBox = document.getElementById("logBox");
	}

	/**
	 * Add new entry to supportchat log messages
	 *
	 * @param {string} crdate
	 * @param {string} message
	 */
	addLogMessage(crdate, message) {
		this.idLogBox.appendChild(this.createLogNode(crdate,message));
		this.scrollTextBoxObj(this.idLogBox);
	}

	/**
	 * Create new log message node
	 *
	 * @param {string} crdate
	 * @param {string} message
	 *
	 * @return {HTMLParagraphElement} msg
	 */
	createLogNode(crdate, message) {
		let msg = document.createElement("p");
		let date = document.createElement("span");
		date.className = "date";
		date.innerText = crdate + " > ";
		let text = document.createElement("span");
		date.className = "log-message";
		date.innerText = message;
		msg.append(date,text);
		return msg;
	}

	/**
	 * Removes / empty log messages from logging box
	 */
	removeLogNodes() {
		this.idLogBox.innerHTML = "";
	}

	/**
	 * Timer function of asking scroll text to bottom object
	 *
	 * @param elem				DOM Element
	 * @param {int} delay	Difference of time in ms to request again
	 */
	scrollTextBox(elem, delay) {
		window.setTimeout(function periodic(delay) {
			this.scrollTextBoxObj(elem);
			window.setTimeout(periodic.bind(this), delay * 1000 || 1000)
		}.bind(this), delay * 1000 || 1000);
	}

	/**
	 * Scroll to bottom object
	 *
	 * @param elem DOM Element
	 */
	scrollTextBoxObj(elem) {
		let shouldScroll = elem.scrollTop + elem.clientHeight === elem.scrollHeight;
		if (!shouldScroll) {
			elem.scrollTop = elem.scrollHeight;
		}
	}
}

/**
 * Class Message
 */
class Message {

	/**
	 * Constructor
	 *
	 * @constructor
	 * @param {string} crdate
	 * @param code
	 * @param {string} name
	 * @oaram {string} message
	 */
	constructor(
			crdate,
			code,
			name,
			message
	) {
		this.crdate = crdate;
		this.code = code;
		this.name = name;
		// define this.message
		if (this.code == "beuser" || this.code == "feuser") {
			for (let [key, img] of Object.entries(supportChatSmilies)) {
				// Unresolved assetsPath
				let theImg = '<img src="' + assetsPath + 'Images/smileys/' + img + '" />';
				message = this.strReplace(key, theImg, message);
			}
		}
		this.message = message;
	}

	/**
	 * String replace
	 *
	 * @param {string} search
	 * @param {string} replace
	 * @param subject
	 *
	 * @return {string}
	 */
	strReplace(search, replace, subject) {
		return subject.split(search).join(replace);
	}

	/**
	 * Create a message node - a message line at the chat
	 *
	 * @return {HTMLParagraphElement} msg
	 */
	getNode() {
		// create the message HTML nodes
		let msg = document.createElement("p");
		let date = document.createElement("span");
		date.className = "date";
		date.innerText = this.crdate+" > ";
		let name = document.createElement("span");
		name.innerText = this.name+" > ";
		switch (this.code) {
			case "beuser":
				name.className = "be_user_message";
				break;
			case "feuser":
				name.className = "fe_user_message";
				break;
			default:
				name.className = "system_message";
		}
		let message = document.createElement("span");
		message.className = "message";
		message.innerHTML = this.message;
		msg.append(date, name, message);
		return msg;
	}
}
