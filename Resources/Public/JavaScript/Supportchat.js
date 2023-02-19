/**** the support chat ****/
function initChat(eidUrl) {
	checkJs();
	initAjaxChat(eidUrl);
}

function checkJs() {
	document.getElementById("chaterror").style.display = 'none';
	document.getElementById("chatboxouter").style.display = 'block';
}

function initAjaxChat(eidUrl) {
	//define chat globally so that chatDestroy() can be performed without moocode
	/** tradem Pass useTypingIndicator as constructor parameter */
	chat = new AjaxChat(
			globPid,
			globLang,
			globFreqMessages,
			timeFormated,
			useTypingIndicator,
			{"eidUrl": eidUrl}
	);
	chat.createChat();
}

/**
 *  Frontend chat class
 */
class AjaxChat {

	/**
	 * Constructor
	 *
	 * @constructor
	 * @param pid 	Process id
	 * @param lang 	Language
	 * @param freq	Frequency of loading chat
	 * @param time
	 * @param useTypingIndicator	Boolean of typing feature
	 * @param options	Additional options
	 *
	 * @return void
	 */
	constructor(pid, lang, freq, time, useTypingIndicator, options) {
		this.options = {
			"id": {
				"textBox": "textBox",
				"sendButton": "sendMessage",
				"closeButton": "chatClose",
				"headline": "chatboxTitle",
				"chatbox": "supportchatbox"
			}
		};
		this.options = this.extendObj(this.options, options);
		this.pid = pid;
		this.lang = lang;
		this.freq = freq;
		this.strftime = time;
		/** tradem 2012-04-11 Used to control notification & display of typing indicator */
		this.useTypingIndicator = useTypingIndicator;
		this.msgToSend = Array(); // storage for messages to send at the next request
		this.inactive = 0;
		this.typingStatus = 0;  // flag that represents the current typing status
		document.getElementById(this.options.id.textBox).focus();
	}

	/**
	 * Adds javascript events to chat frontend
	 */
	addEvents() {
		// insertMessage Button, close chat button,textarea onPressEnter
		document.getElementById(this.options.id.sendButton).addEventListener(
				"click",
				this.createMessage.bind(this)
		);
		document.getElementById(this.options.id.textBox).addEventListener(
			"keyup",
			function(event) {
					if (event.key === "Enter") {
						event.preventDefault();
						this.createMessage(event);
					}
			}.bind(this)
		);
		/* Register user typing event */
		if (this.useTypingIndicator === 1) {
			document.getElementById(this.options.id.textBox).addEventListener(
				"keyup",
				function(event) {
					if (event.key !== "Enter") {
						event.preventDefault();
						this.setTypingState();
					}
				}.bind(this)
			);
		}
		/* Register event of close button*/
		document.getElementById(this.options.id.closeButton).addEventListener(
				"click",
				function () {
					this.destroyChat();
					var close_button_flag = true; // seems of no use
				}.bind(this));
		let exportBtn = document.getElementById("exportButton");
		if (exportBtn) {
			exportBtn.addEventListener(
					"click",
					function () {
						document.getElementById("data1").value =
								document.getElementById("supportchatbox").innerHTML;
			});
		}
	}

	/**
	 * Clear timer
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
	 * Create chat frontend
	 */
	createChat() {
		/* the init function for creating a new chat */
		fetch(this.options.eidUrl + "&cmd=createChat&pid=" + this.pid + "&L=" +
					this.lang + "&useTypingIndicator=" + this.useTypingIndicator)
		.then((res) => res.json())
		.then((data) => {
			this.uid = this.toInt(data);
			if (this.uid) {
				// the chat was created successfully
				// write system welcome message
				this.insertMessage(
						diffLang.chatWelcome,
						"system",
						diffLang.system,
						this.strftime
				);
				// create the unique request object
				/*fetch(this.options.eidUrl + "&pid=" + this.pid + "&chat=" +
						this.uid + "&useTypingIndicator=" + this.useTypingIndicator)
				.then((res) => res.text())
				.then((data) => {
						this.requestDone.bind(this);
				});*/
				// call the getMessages function periodically
				this.getAllInterval(this.freq);
				// create the button events
				this.addEvents();
			} else {
				this.insertMessage(
						"The chat could not be created! Please inform the site admin.",
						"system",
						diffLang.system,
						this.strftime
				);
			}
		});
	}

	/**
	 * Create message. Called if a new message is posted
	 *
	 * @param event
	 */
	createMessage(event) {
			let message = document.getElementById(this.options.id.textBox).value;
      if (message) {
        this.msgToSend.push(message); // gather it in array for next request
        this.insertMessage(
        		this.stripScripts(message),
						"feuser",
						diffLang.chatUsername,
						this.strftime
				); // insert it locally (now)
				document.getElementById(this.options.id.textBox).value = "";
      }
      document.getElementById(this.options.id.textBox).focus();
      event.stopPropagation(); // prevent standard events for onEnterButton
    }

	/**
	 * Destroy chat window
	 *
	 * @type {*}
	 */
	 destroyChat() {
		this.clearTimer(this.timer);
		this.inactive = 1;
		// write system chat destroyed message
		this.insertMessage(
				diffLang.systemByeBye,
				"system",
				diffLang.system,
				this.strftime
		);
		this.removeEvents();
		fetch(this.options.eidUrl + "&cmd=destroyChat&chat=" + this.uid + "&pid="
				+ this.pid + "&useTypingIndicator=" + this.useTypingIndicator)
		.then((res) => {
			if (!res.ok) {
				throw new Error(
						"HTTP error at destroyChat() GET request " + res.status);
			}
			return res.text();
		})
		.then(() => {
			this.sleep(5);
			window.close();
		});
  }

	/**
     * Helper funtion to merge objects to one extendObj(obj1, obj2)
     *
     * @returns {{}}
     */
	extendObj() {
		// Variables
		let extended = {};
		let deep = false;
		let i = 0;

		// Check if a deep merge
		if (Object.prototype.toString.call(arguments[0] ) === '[object Boolean]') {
			deep = arguments[0];
			i++;
		}

		// Merge the object into the extended object
		let merge = function (obj) {
			for (let prop in obj) {
				if (obj.hasOwnProperty(prop)) {
					// If property is an object, merge properties
					if (deep && Object.prototype.toString.call(obj[prop]) === '[object Object]') {
						extended[prop] = extend(extended[prop], obj[prop]);
					} else {
						extended[prop] = obj[prop];
					}
				}
			}
		};

		// Loop through each object and conduct a merge
		for (; i < arguments.length; i++) {
			merge(arguments[i]);
		}
		return extended;
	}

	/**
	 * This function is called with a delay by itself, get all messages or
	 * rather post new messages to/from server
	 */
	getAll() {
		let postMessages = "";
		this.msgToSend.forEach(function (item, index) {
			if (item) {
				postMessages += "&msgToSend[" + index + "]=" + encodeURIComponent(item);
			}
		}.bind(this));
		if (postMessages) {
			postMessages += "&chatUsername=" + diffLang.chatUsername;
		}
		this.msgToSend.length = 0; // Clean the array of post messages
		/* Reference to request object of createChat */
		fetch(this.options.eidUrl + "&pid=" + this.pid + "&chat=" +
				this.uid + "&useTypingIndicator=" + this.useTypingIndicator,
				{
					method: 'POST',
					cache: 'no-cache',
					credentials: 'same-origin',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded'
					},
					body: "cmd=getAll" + "&useTypingIndicator=" + this.useTypingIndicator
							+ "&lastRow=" + this.lastRow + postMessages
							+ "&isTyping=" + this.typingStatus
				}
		)
		.then((res) => {
			if (!res.ok) {
				throw new Error(
						"HTTP exception at POST request in function AjaxChat.getAll() with status:" + response.status);
			}
			return res.json();
		})
		.then((data) => {
				this.processPostRequest(data);
		});
	}

	/**
	 * Periodic caller of getAll()
	 *
	 * @param int frequency
	 */
	getAllInterval(frequency) {
		/*this.timer = window.setTimeout(function periodic(frequency) {
			this.getAll();
			window.setTimeout(periodic.bind(this), frequency || 1000)
		}.bind(this), frequency || 1000);*/
		this.timer = setInterval(this.getAll.bind(this), frequency || 1000);
	}

	/**
	 * Inserts a Message at the chatbox (HTML) and scrolls the textbox to bottom
	 *
	 * @param message	Text message
	 * @param code		Type of message posts from client or supporter
	 * @param name		Name of posting person
	 * @param time
	 *
	 * @return null
	 */
	insertMessage(message, code, name, time) {

		if (code !== "system") {
			for (let [key, img] of Object.entries(supportChatSmilies)) {
				let theImg = '<img src="typo3conf/ext/supportchat/pics/smiley/' + img + '" />';
				message = this.strReplace(key, theImg, message);
			}
		}
		
		if (code !== "title") {
			let user = '';
			switch (code) {
				case 'system':
					user = '<span class="system-message">' + name + ' > </span>';
					break;
				case 'beuser':
					user = '<span class="supportler-message">' + name + ' > </span>';
					break;
				default:
					// fe-user message
					user = '<span>' + name + ' > </span>';
			}
			let allWrap = '<span class="date">' + time + ' > </span> ' + user;
			let msgEl = document.createElement("span");
				msgEl.className = "message";
				msgEl.innerHTML = message;
			let newLine = document.createElement("p");
				newLine.innerHTML = allWrap;
			newLine.appendChild(msgEl);
			document.getElementById(this.options.id.chatbox).appendChild(newLine);
			this.scrollTextbox(120);
		} else {
			// insert a new title in top of the chatbox
			document.getElementById(this.options.id.headline).textContent(message);
		}
		return null;
	}

	/**
	 * Procees post request
	 *
	 * @param {json} json response
	 *
	 */
	processPostRequest(json) {
		// the onComplete function of the unique AJAX Request
		if (!json.hasOwnProperty("time")) {
			throw new Error("No valid JSON received in response. Property 'time' has set.")
		}
		this.strftime = json.time;
		// check if status key of json is set
		if (json.hasOwnProperty("status")) {
			switch (json.status) {
				case 'timeout':
					this.insertMessage(
							diffLang.chatTimeout,
							"system",
							diffLang.system,
							this.strftime
					);
					break;
				case 'be_user_destroyed':
					this.insertMessage(
							diffLang.chatDestroyedByAdmin,
							"system",
							diffLang.system,
							this.strftime
					);
					break;
				case 'no_access':
					this.insertMessage(
							diffLang.chatNoAccess,
							"system",
							diffLang.system,
							this.strftime
					);
					break;
			}
			this.removeEvents();
			this.inactive = 1;
			this.clearTimer(this.timer);
			// refresh message array
		} else {
			this.lastRow = json.lastRow; // update last Row
			if (this.useTypingIndicator == 1) {
				let typingElement = (json.typingStatus == 1) ? "inline" : "none";
				document.getElementById('typingPen').style.display = typingElement;
			}
			if (json.hasOwnProperty("messages") && json.messages.length > 0) {
				for (let i = 0; i < json.messages.length; i++) {
					this.insertMessage(
							json.messages[i].message, //
							json.messages[i].code, // code
							json.messages[i].name, // name
							json.messages[i].crdate // date
					);
				}
			}
		}
	}

	/**
	 * Remove javascript events
	 */
	removeEvents() {
		document.getElementById(this.options.id.sendButton)
			.removeEventListener('click', this.createMessage);
		document.getElementById(this.options.id.textBox)
			.removeEventListener('keyup', this.createMessage);
		document.getElementById(this.options.id.closeButton)
			.removeEventListener('click', this.destroyChat);
		/*let elements = ['sendButton','textBox','closeButton'];
		for (let i = 0; i < elements.length; $i++) {
			let domElement = 'this.options.id.' + elements[i];
			let elem = document.getElementById(domElement)
			elem.replaceWith(elem.cloneNode(true));
		}*/
	}

	/**
	 * Request done
	 *
	 * @param respText
	 * @param respXML
	 */
	requestDone (respText, respXML) {
		/* the onComplete function of the unique AJAX Request */
		if (respXML) {
			let root = respXML.getElementsByTagName("phparray");
			if (root) {
				this.strftime = root[0].childNodes[0].firstChild.nodeValue; // update Time
				if (root[0].childNodes[1].nodeName == "status") {
					/* no access to the chat, show message why ,remove Events and stop polling */
					switch (root[0].childNodes[1].firstChild.nodeValue) {
						case 'timeout':
							this.insertMessage(
									diffLang.chatTimeout,
									"system",
									diffLang.system,
									this.strftime
							);
							break;
						case 'be_user_destroyed':
							this.insertMessage(
									diffLang.chatDestroyedByAdmin,
									"system",
									diffLang.system,
									this.strftime
							);
							break;
						case 'no_access':
							this.insertMessage(
									diffLang.chatNoAccess,
									"system",
									diffLang.system,
									this.strftime
							);
							break;
					}
					this.removeEvents();
					this.inactive = 1;
				} else {
					this.lastRow = root[0].childNodes[1].firstChild.nodeValue; // update last Row
					if (this.useTypingIndicator == 1) {
						let status = root[0].childNodes[3].firstChild.nodeValue; // get Status (is Typing ecc.)
						if (status == 1) {
							document.getElementById('typingPen').setAttribute("display", "inline");
						} else {
							document.getElementById('typingPen').setAttribute("display", "none");
						}
					}
					if (root[0].childNodes[2]) {
						let messages = root[0].childNodes[2].getElementsByTagName("numIndex");
						if (messages.length > 0) {
							for (let i = 0; i < messages.length; i++) {
								let date = messages[i].childNodes[0].firstChild.nodeValue;
								let code = messages[i].childNodes[1].firstChild.nodeValue;
								let name = messages[i].childNodes[2].firstChild.nodeValue;
								let message = messages[i].childNodes[3].firstChild.nodeValue;
								this.insertMessage(message, code, name, date);
							}
						}
					}
				}
			}
		}
		if (!this.inactive) {
			// call the get Messages function with delay
			this.clearTimer(this.timer);
			// prototype function
			this.timer = this.getAll.delay(this.freq);
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
	 * Timer function of asking scroll text to bottom object
	 *
	 * @param delay	Difference of time in ms to request again
	 */
	scrollTextbox(delay) {
		this.scrollTextBoxObj();
		window.setTimeout(function periodic(delay) {
			this.scrollTextBoxObj();
			window.setTimeout(periodic.bind(this), delay * 1000 || 1000)
		}.bind(this), delay * 1000 || 1000);
	}

	/**
	 * Scroll to bottom object
	 */
	scrollTextBoxObj() {
		let elm = document.getElementById(this.options.id.chatbox);
		let shouldScroll = elm.scrollTop + elm.clientHeight === elm.scrollHeight;
		if (!shouldScroll) {
			elm.scrollTop = elm.scrollHeight;
		}
	}

	/**
	 * Sets typing state, whenever a keydown event has been fired
	 */
	setTypingState() {
		this.clearTimer(this.resetTimer);
		this.typingStatus = 1;
		//this.resetTimer = this.resetTypingState.delay(this.freq + 500, this);
		window.setTimeout(function() {
			this.typingStatus = 0;
		}.bind(this), this.freq || 2000);
	}

	/**
	 * Sleep function
	 *
	 * @param int seconds
	 */
	sleep(seconds) {
		const date = Date.now();
		let currentDate = null;
		do {
			currentDate = Date.now();
		} while (currentDate - date < (seconds * 1000));
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
	 * Helper function to string replace
	 *
	 * @param search
	 * @param replace
	 * @param subject
	 *
	 * @returns {*}
	 */
	strReplace(search, replace, subject) {
		return subject.split(search).join(replace);
	}

	/**
	 * Helper function to type cast number to int
	 *
	 * @param number
	 * @param base
	 *
	 * @returns {number}
	 */
	toInt(number, base) {
		return parseInt(number,base||10);
	}
}


