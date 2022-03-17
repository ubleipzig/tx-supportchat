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

export {Message};