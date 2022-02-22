import React from 'react';
import { Icon } from '@discuzq/design';
import classNames from 'classnames';
import styles from './index.module.scss';

export default class CustomReforwardPost extends React.PureComponent {
  constructor(props) {
    super(props);
    this.state = {};
  }

  render() {
    const { siteData, renderData, deletePlugin, _pluginInfo, updatePlugin } =
      this.props;
    const isPC = siteData.platform === 'pc';
    if (!renderData) return null;

    const { body } = renderData || {};

    return (
      <div
        className={classNames(
          styles['dzqp-post-widget'],
          isPC && styles['dzqp-pc']
        )}
        onClick={(e) => {
          e.stopPropagation();
          updatePlugin({ postData: renderData, _pluginInfo, isShow: true });
        }}>
        <div className={styles['dzqp-post-widget__right']}>
          <Icon
            className={styles['dzqp-post-widget__icon']}
            name="WithdrawOutlined"
          />
          <span className={styles['dzqp-post-widget__text']}>
            将会转发 id 为 {body.threadId} 的帖子
          </span>
        </div>
        <Icon
          className={styles['dzqp-post-widget__left']}
          name="DeleteOutlined"
          onClick={(e) => {
            e.stopPropagation();
            deletePlugin();
          }}
        />
      </div>
    );
  }
}
