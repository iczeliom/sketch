import * as React from 'react';
import { Pagination, Card } from '../common';
import { ResData } from '../../../config/api';
import { ROUTE } from '../../../config/route';
import { Link } from 'react-router-dom';

interface Props {
    threads:ResData.Thread[];
    paginate:ResData.ThreadPaginate;
}

interface State {
}

export class BookList extends React.Component<Props, State> {
    public render () {
        return <Card className="book-index">
            <Pagination
                currentPage={this.props.paginate.current_page}
                lastPage={this.props.paginate.total_pages} />
            <div className="thread-form">
                {this.props.threads.map((thread) => this.renderBookItem(thread))}
            </div>
        </Card>;
    }

    public renderBookItem (thread:ResData.Thread) {
        const { attributes, id, author, tags, last_component } = thread;
        return <div className="thread-list" key={id}>
            <Link className="title" to={`/book/${id}`}>{ attributes.title }</Link>
            <div className="biref">{ attributes.brief }</div>
            { last_component &&
                <Link className="latest-chapter" to={`/book/${id}/chapter/${last_component.id}`}>最新章节: { last_component.attributes.title }</Link>
            }
            { tags &&
                <div className="tags">
                    { tags.map((tag, i) => 
                        <a className="tag" key={i} href={`${window.location.origin}/books/?tag=${tag.id}`}>{tag.attributes.tag_name}</a>
                    )}
                </div> 
            }
            <div className="meta">
                <Link className="author" to={`${ROUTE.users}/${author.id}`}>{author.attributes.name}</Link>
                <div className="counters">
                    <span><i className="fas fa-pencil-alt"></i>{attributes.total_char}</span> /
                    <span><i className="fas fa-eye"></i>{attributes.view_count}</span> / 
                    <span><i className="fas fa-comment-alt"></i>{attributes.reply_count}</span> /
                </div>
            </div>
            <hr />
        </div>;
    }
}