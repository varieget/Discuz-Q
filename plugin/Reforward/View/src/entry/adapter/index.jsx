import React from "react";
import { Icon, Dialog, Button, Input } from "@discuzq/design";

export default class CustomReforward extends React.PureComponent {
    constructor(props) {
        super(props);
        this.state = {
            visible: false,
            threadId: 1,
        };
    }

    handleChange(event) {
        this.setState({
            threadId: +event.target.value,
        });
    }

    render() {
        return (
            <>
                <Icon
                    onClick={(e) => {
                        e.stopPropagation();
                        this.setState({ visible: true });
                    }}
                    name="WithdrawOutlined"
                    size="20"
                />
                <Dialog visible={this.state.visible}>
                    <Input
                        value={this.state.threadId}
                        onChange={this.handleChange.bind(this)}
                        placeholder="threadId"
                    />
                    <Button
                        onClick={() => {
                            this.props.onConfirm({
                                postData: {
                                    tomId: "reforward",
                                    body: {
                                        threadId: this.state.threadId,
                                    },
                                },
                            });

                            this.setState({ visible: false });
                        }}
                    >
                        确定
                    </Button>
                    <Button
                        onClick={() => {
                            this.setState({ visible: false });
                        }}
                    >
                        关闭
                    </Button>
                </Dialog>
            </>
        );
    }
}
