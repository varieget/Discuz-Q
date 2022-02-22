import React from "react";
import { Icon } from "@discuzq/design";
import classNames from "classnames";
import styles from "./index.module.scss";

export default class CustomIframDisplayContent extends React.PureComponent {
    constructor(props) {
        super(props);
        this.state = {};
    }

    render() {
        const {
            siteData,
            renderData,
            deletePlugin,
            _pluginInfo,
            updatePlugin,
        } = this.props;
        const isPC = siteData.platform === "pc";
        if (!renderData) return null;

        const { body, tomId } = renderData;

        return (
            <div
                className={classNames(
                    styles["dzqp-post-widget"],
                    isPC && styles["dzqp-pc"]
                )}
            >
                <div className={styles["dzqp-post-widget__right"]}>
                    <Icon
                        className={styles["dzqp-post-widget__icon"]}
                        name="WithdrawOutlined"
                    />
                    <span className={styles["dzqp-post-widget__text"]}>
                        转发了 {body.threadId}
                    </span>
                </div>
            </div>
        );
    }
}
