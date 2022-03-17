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
    return (this.beUsers);
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

export {BackendUser};
