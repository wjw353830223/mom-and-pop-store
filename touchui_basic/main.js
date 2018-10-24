import './static/utils/jquery'
import './static/utils/jquery.sha1'
import {fetchs,estiblashWs,visibilitychange,websocket_base_path} from "./static/utils/request";
import app from './app.ui'
let options = {
  app: app
}
ui.extend({
  fetchs:fetchs,
  estiblashWs:estiblashWs,
  visibilitychange:visibilitychange,
  websocket_base_path:websocket_base_path
})
ui.start(options)