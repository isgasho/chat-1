function IOClass()
{
    this.encode = function (type, data) {

        let obj = {type: type, data: data};
        return JSON.stringify(obj);
    };

    this.decode = function (value) {

        return JSON.parse(value);
    };
}


function CharClass() {
    // 当前聊天的对象, 如果不在聊天窗口则为null
    this.chatId = null;
    // 总聊天数目
    this.totalNumber = 0;
    // key 为用户的 ID, value 是一个 User 对象
    this.users = {};

    this.htmlBuilder = new HtmlBuilder();

    // 用户未读消息数目
    this.updateTotalNumber = function (val) {

        this.totalNumber += val;

        $('#total-message').html(this.totalNumber);
    };
    // 更新用户的未读消息数目
    this.updateUserUnreadCount = function (id, val) {

        // 如果从来没有过消息
        if (! (this.users[id] instanceof Object)) {

            alert('未知用户的消息');
            return;
        }

        let messageCount = this.getUserUnreadCount(id) + val;
        this.users[id].message_count = messageCount;

        $('#user-list-'+id).find('.message-num').text(messageCount)
    };
    this.getUserUnreadCount = function (id) {

        // 如果从来没有过消息
        if (! (this.users[id] instanceof Object)) {

            return 0;
        }

        return this.users[id].message_count;
    };

    // 用户的所有消息
    this.setMessage = function (fromId, msgModel) {

        if (! (msgModel instanceof MessageClass)) {
            alert('错误的消息类型');
            return;
        }

        // 没有这个用户
        if (! (this.users[fromId] instanceof Object)) {

            alert('未知用户的消息');
            return;
        }

        // 增条一条消息记录
        this.users[fromId].messages.push(msgModel);
    };

    this.setUser = function (user) {

        if (! (user instanceof Object)) {
            alert('用户类不合法');
            return;
        }

        user = new UserClass(user.id, user.username, user.avatar);
        this.users[user.id] = user;
        // 如果从来没有过消息
        $('#user_list').append(this.htmlBuilder.user(user));
    };

    this.getUser = function (id) {

        if (! (this.users[id] instanceof Object)) {
            alert('错误的用户');
            return;
        }

        return this.users[id];
    };

    this.updateUserStatus = function (id, status) {

        this.users[id].status = status;

        let dom = $('#user-list-' + id);
        if (status === UserClass.USER_STATUSES.LEAVE) {

            dom.attr('title', '下线').css({background: '#d2d2d2'});
        } else if (status === UserClass.USER_STATUSES.BATTLE) {

            dom.attr('title', '战斗中').css({background: '#FF5722'});
        } else {

            dom.attr('title', '在线').css({background: '#fff'});
        }
    };

    this.updateSelfInfo = function (user) {

        $('#self-avatar').attr('src', user.avatar);
        $('#self-username').text(user.username);
    };
}

/**
 * 构建 HTML 标签
 * @constructor
 */
function HtmlBuilder()
{
    this.user = function (user) {

        return '<div class="chat-list-people" id="user-list-'+ user.id +'" data-id="'+ user.id +'" title="在线">\n' +
            '     <div><img class="chat-avatars" src="'+ user.avatar +'" alt="头像"/></div>\n' +
            '     <div class="chat-name">\n' +
            '       <p>'+ user.username +'</p>\n' +
            '     </div>\n' +
            '   <div class="message-num">0</div>\n' +
            ' </div>';
    };

    this.msg = function (msg) {

        let dir = msg.isMe ? 'right' : 'left';

        return "<div class=\"clearfloat\">" +
            "<div class=\"author-name\"><small class=\"chat-date\">"+ msg.time +"</small> </div> " +
            "<div class=\""+ dir +"\"> <div class=\"chat-message\"> " + msg.content + " </div></div>" +
            "</div>";
    };
}


/**
 * 用户对象
 *
 * @param id
 * @param name
 * @param avatar
 * @constructor
 */
function UserClass(id, name, avatar)
{
    UserClass.USER_STATUSES = {
        // 上线
        ACTIVE: 1,
        // 下线
        LEAVE: 2,
        // 战斗中
        BATTLE: 3,
    };

    this.id = id;
    this.username = name;
    this.avatar = avatar;

    // 聊天记录
    this.message_count = 0;
    this.messages = [];

    // 用户当前的状态
    this.status = UserClass.USER_STATUSES.ACTIVE;
}

/**
 * 消息对象
 * @param time
 * @param content
 * @param isMe
 * @constructor
 */
function MessageClass(time, content, isMe)
{
    this.time = time;
    this.content = content;
    this.isMe = isMe;
}


function optinal(obj, key, def) {

    if (! obj) {

        return def;
    }


    return obj[key] || def;
}

// 发送弹幕
function barrage(msg, isColor) {

        let color = isColor ? '#98FB98' : '#fff';

        let item = {
            info: msg, //文字
            close:true, //显示关闭按钮
            speed:6, //延迟,单位秒,默认6
            color: color, //颜色,默认白色
            old_ie_color:'#ffffff', //ie低版兼容色,不能与网页背景相同,默认黑色
        };

        $('#messages').barrager(item);
}


function now()
{
    let date = new Date();

    return date.getFullYear() + '-' + (date.getMonth() + 1) + '-' + date.getDate() + ' ' + (date.getHours() + 1) + ':' + (date.getMinutes()) + ':' + date.getSeconds();
}
