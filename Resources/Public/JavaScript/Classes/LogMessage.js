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
    this.scrollTextBox(this.idLogBox, 100);
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
      date.innerText = this.crdate + " > ";
    let text = document.createElement("span");
      date.className = "log-message";
      date.innerText = this.message;
    msg.append(date,text);
    return msg;
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

export {LogMessage};