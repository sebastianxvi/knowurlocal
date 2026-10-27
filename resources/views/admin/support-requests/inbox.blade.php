@extends('layouts.admin')
@section('title', 'KNOWURLOCAL | Support Inbox')
@section('page-title', 'Support Inbox')
@section('page-subtitle', 'Review citizen support requests across agencies')
@push('styles')
<style>
.agency-inbox{height:calc(100dvh - 190px);min-height:420px;display:grid;grid-template-columns:minmax(270px,330px) minmax(0,1fr);border:1px solid var(--border-color,#dfe5ee);border-radius:16px;overflow:hidden;background:#fff;color:var(--text-primary,#172033)}
.ai-sidebar{min-width:0;border-right:1px solid #e1e6ee;display:flex;flex-direction:column;background:#fff}.ai-sidebar-head{padding:20px 17px 14px}.ai-sidebar-head h2{font-size:22px;margin:0 0 4px}.ai-sidebar-head p{margin:0;color:#64748b;font-size:12px}.ai-search{margin-top:15px;width:100%;padding:11px 12px;border:1px solid #dce3ee;border-radius:11px;font:inherit;font-size:13px;box-sizing:border-box}.ai-list{overflow:auto;min-height:0;padding:0 9px 12px}.ai-item{display:block;padding:13px 12px;margin:4px 0;border-radius:12px;text-decoration:none;color:inherit;border:1px solid transparent}.ai-item:hover{background:#f6f8fc}.ai-item.active{background:#eaf1ff;border-color:#dce7ff}.ai-item-top{display:flex;gap:10px;align-items:center}.ai-avatar{width:40px;height:40px;border-radius:50%;display:grid;place-items:center;flex:0 0 40px;background:#e8f0ff;color:#2859a8;font-size:13px;font-weight:800}.ai-item-name{font-weight:750;font-size:13px;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.ai-item-date{font-size:10px;color:#64748b;margin-left:auto;white-space:nowrap}.ai-preview{margin:8px 0 0 50px;font-size:12px;color:#64748b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.ai-unread-dot{display:inline-block;width:9px;height:9px;flex:0 0 9px;border-radius:50%;background:#dc2626;box-shadow:0 0 0 3px #fee2e2;vertical-align:middle;margin-left:7px}.ai-item-top .ai-unread-dot{margin-left:auto}.ai-status{display:inline-block;margin:7px 0 0 50px;font-size:10px;padding:4px 7px;border-radius:99px;background:#f1f5f9;color:#475569}.ai-main{min-width:0;min-height:0;height:100%;display:flex;flex-direction:column;overflow:hidden}.ai-chat-head{padding:16px 20px;border-bottom:1px solid #e1e6ee;display:flex;align-items:center;gap:12px}.ai-chat-head h2{font-size:15px;margin:0}.ai-chat-head p{font-size:11px;color:#64748b;margin:4px 0 0}.ai-ticket-picker{padding:12px 18px;border-bottom:1px solid #e1e6ee;background:#fbfcfe}.ai-ticket-picker-label{font-size:10px;letter-spacing:.06em;text-transform:uppercase;font-weight:800;color:#64748b;margin:0 0 9px}.ai-ticket-options{display:flex;gap:8px;overflow-x:auto;padding:0 0 3px}.ai-ticket-option{flex:0 0 min(280px,75%);min-width:0;padding:10px 12px;border:1px solid #dce3ee;border-radius:11px;text-decoration:none;color:inherit;background:#fff}.ai-ticket-option.active{background:#eaf1ff;border-color:#b8ceff}.ai-ticket-option strong{display:block;font-size:12px;margin-bottom:5px}.ai-ticket-option span{display:block;font-size:11px;color:#64748b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.ai-ticket-option em{display:inline-block;margin-top:6px;font-size:10px;font-style:normal;color:#475569}.ai-messages{flex:1 1 0;overflow-y:auto;overflow-x:hidden;min-height:0;min-width:0;overscroll-behavior:contain;padding:22px clamp(14px,3vw,42px);background:#fff}.ai-ticket-intro{max-width:1000px;margin:0 auto 20px;padding:16px;border:1px solid #dfe5ee;border-radius:13px;background:#f8fafc}.ai-ticket-intro small{color:#64748b;font-weight:750;text-transform:uppercase;letter-spacing:.05em}.ai-ticket-intro h3{font-size:15px;margin:7px 0}.ai-ticket-intro p{white-space:pre-wrap;line-height:1.6;font-size:13px;margin:0}.ai-response{width:100%;max-width:100%;margin:0 0 16px}.ai-response .ai-bubble{width:100%;box-sizing:border-box}.ai-follow-up{display:flex;justify-content:flex-end;margin:0 0 18px 12%;}.ai-follow-up-bubble{max-width:min(78%,720px);padding:12px 15px;border:1px solid #cbdcff;border-radius:16px 16px 4px 16px;background:#eaf1ff;color:#1e3a68;font-size:13px;line-height:1.6;overflow-wrap:anywhere}.ai-follow-up-label{font-size:10px;font-weight:750;color:#4569a8;margin-bottom:4px}.ai-follow-up-empty{color:#64748b;font-style:italic}.ai-response-meta{font-size:11px;color:#64748b;margin:0 0 7px 3px}.ai-bubble{padding:14px 16px;border:1px solid #dce4f0;border-radius:14px;background:#f8fafc;font-size:13px;line-height:1.6;overflow-wrap:anywhere}.ai-component{margin:8px 0;min-width:0}.ai-component img{max-width:100%;max-height:360px;border-radius:9px;display:block}.ai-component a{color:#2859a8;overflow-wrap:anywhere}.ai-response-image-wrap{display:flex;flex-direction:column;align-items:flex-start;gap:6px;max-width:100%}.ai-response-image-trigger{display:block;width:fit-content;max-width:min(100%,420px);padding:0;border:1px solid #e3e8f2;border-radius:10px;overflow:hidden;background:#fff;cursor:zoom-in}.ai-response-image-trigger img{display:block;width:auto;max-width:100%;max-height:320px;object-fit:contain}.ai-component-label{display:block;font-size:11px;color:#475569;font-weight:650;overflow-wrap:anywhere}.ai-response-file,.ai-response-link{display:flex;align-items:center;gap:10px;width:100%;box-sizing:border-box;padding:10px 11px;border:1px solid #e3e8f2;border-radius:12px;background:rgba(255,255,255,.92);color:#202938!important;text-decoration:none;overflow-wrap:anywhere}.ai-response-file:hover,.ai-response-link:hover{border-color:#5272c9;background:#f8faff;text-decoration:none}.ai-response-qr{display:flex;flex-direction:column;align-items:center;gap:9px;width:100%;padding-top:10px;border-top:1px solid #e3e8f2;text-align:center}.ai-response-qr .ai-component-label{width:100%;text-align:center}.ai-response-qr .response-qr-code{display:flex;align-items:center;justify-content:center;margin-inline:auto;padding:10px;border:1px solid #e3e8f2;border-radius:12px;background:#fff}.ai-response-qr .response-qr-code svg{display:block;max-width:100%;height:auto}.ai-response-qr>a{align-self:center}.ai-compose{border-top:1px solid #e1e6ee;padding:13px 18px;background:#fff}.ai-compose-title{font-size:12px;font-weight:750;margin:0 0 10px}.ai-component-list{display:grid;gap:9px}.ai-compose-row{display:grid;grid-template-columns:112px minmax(0,1fr) 42px;gap:8px;align-items:center}.ai-compose-field{position:relative;min-width:0}.ai-compose-field textarea,.ai-compose-field input{padding-right:38px!important}.ai-compose-field .ai-icon-btn.remove{position:absolute;right:5px;top:5px;width:26px;height:26px;border-radius:8px;z-index:1}.ai-compose-row .ai-icon-btn.send{align-self:center;width:42px;height:42px}.ai-compose-row select,.ai-compose-row input,.ai-compose-row textarea{width:100%;min-width:0;box-sizing:border-box;border:1px solid #dce3ee;border-radius:12px;padding:11px 12px;font:inherit;font-size:12px;background:#fff;color:#172033;outline:none}.ai-compose-row select:focus,.ai-compose-row input:focus,.ai-compose-row textarea:focus{border-color:#8eafff;box-shadow:0 0 0 3px #eaf1ff}.ai-compose-row textarea{min-height:46px;height:46px;max-height:220px;overflow-y:hidden;resize:none;line-height:1.5}.ai-compose-row input[type=file]{padding:8px}.ai-compose-actions{display:flex;align-items:center;gap:10px;margin-top:9px}.ai-compose-note{font-size:11px;color:#64748b;flex:1;line-height:1.45}.ai-icon-btn{width:38px;height:38px;display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:12px;background:#f1f5f9;color:#475569;cursor:pointer;flex:0 0 auto;transition:background .15s,color .15s,transform .15s}.ai-icon-btn:hover{background:#e7efff;color:#2859a8}.ai-icon-btn:focus-visible{outline:3px solid #c7d8ff;outline-offset:2px}.ai-icon-btn:disabled{opacity:.5;cursor:not-allowed}.ai-icon-btn.send{width:42px;height:42px;border-radius:50%;background:#2861e8;color:#fff;box-shadow:0 3px 8px #2861e82b}.ai-icon-btn.send:hover{background:#1e4fd0;transform:translateY(-1px)}.ai-icon-btn.remove{width:30px;height:30px;border-radius:9px;background:#f8fafc;color:#94a3b8}.ai-icon-btn.remove:hover{background:#fee2e2;color:#b91c1c}.ai-icon-btn.add{width:38px;height:38px;border:1px solid #dce3ee;background:#fff;color:#2859a8}.ai-compose-actions .ai-compose-note{margin-left:2px}.ai-btn:disabled{opacity:.55;cursor:not-allowed}.ai-alert{padding:10px 12px;margin-bottom:10px;border-radius:9px;background:#fff7ed;color:#9a3412;font-size:12px}.ai-empty{margin:auto;text-align:center;padding:28px;color:#64748b;font-size:13px}
@media(max-width:800px){.agency-inbox{height:calc(100dvh - 160px);min-height:360px;grid-template-columns:minmax(120px,34%) minmax(0,1fr)}.ai-sidebar-head{padding:14px 10px}.ai-sidebar-head h2{font-size:18px}.ai-item{padding:10px 7px}.ai-avatar{width:30px;height:30px;flex-basis:30px}.ai-preview,.ai-status{margin-left:0}.ai-compose-row{grid-template-columns:minmax(0,1fr) 42px}.ai-compose-row select{grid-column:1 / -1}.ai-compose-field{grid-column:1}.ai-compose-row .ai-icon-btn.send{grid-column:2;grid-row:2}.ai-compose-actions{align-items:center;flex-direction:row}.ai-compose-note{font-size:10px}.ai-chat-head{padding:12px}.ai-messages{padding:14px 10px}.ai-follow-up{margin-left:4%}.ai-follow-up-bubble{max-width:92%}.ai-ticket-picker{padding:10px}.ai-ticket-option{flex-basis:220px}}
/* Messenger-like sizing: short messages stay compact; long/rich messages can use ~68% of the conversation width. */
.ai-response{display:flex;flex-direction:column;align-items:flex-end;width:100%;margin:0 0 16px;padding-left:4%;box-sizing:border-box}
.ai-response-meta{align-self:flex-end;max-width:min(68%,820px);text-align:right;margin:0 3px 7px 0}
.ai-response .ai-bubble{width:fit-content;max-width:min(68%,820px);min-width:0;box-sizing:border-box;border-radius:16px 16px 4px 16px;background:#eaf1ff;border-color:#cbdcff;color:#1e3a68}
.ai-response .ai-bubble:has(.ai-component){width:min(68%,820px)}
.ai-response .ai-component{max-width:100%;min-width:0;overflow-wrap:anywhere}
.ai-response .ai-component img{max-width:100%;height:auto;object-fit:contain}
.ai-response .ai-component > div{max-width:100%;overflow-wrap:anywhere}
.ai-follow-up{justify-content:flex-start;margin:0 4% 18px 0}
.ai-follow-up-bubble{width:fit-content;max-width:min(68%,820px);border-radius:16px 16px 16px 4px;background:#f8fafc;border-color:#dce4f0;color:#172033}
.ai-follow-up-label{color:#64748b}
@media(max-width:800px){.ai-response{padding-left:2%}.ai-response .ai-bubble,.ai-follow-up-bubble{max-width:88%}.ai-response .ai-bubble:has(.ai-component){width:88%}.ai-follow-up{margin-right:2%}}


.ai-ticket-list-view{flex:1;min-height:0;overflow:auto;padding:18px 20px;background:#fff}
.ai-ticket-list{display:flex;flex-direction:column;gap:10px}
.ai-ticket-list-option{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:17px 18px;border:1px solid #dce3ee;border-radius:13px;text-decoration:none;color:inherit;background:#fff;transition:background .15s,border-color .15s,transform .15s}
.ai-ticket-list-option:hover{background:#f8faff;border-color:#b8ceff;transform:translateY(-1px)}
.ai-ticket-list-main{display:flex;flex-direction:column;gap:7px;min-width:0}
.ai-ticket-list-main strong{font-size:13px;color:#172033}
.ai-ticket-list-main>span{font-size:12px;color:#64748b;white-space:normal;overflow-wrap:anywhere;line-height:1.5}
.ai-ticket-list-meta{display:flex;align-items:center;gap:12px;flex:0 0 auto;font-size:11px;color:#64748b}
.ai-back-to-tickets{display:inline-flex;align-items:center;gap:5px;padding:8px 10px;border:1px solid #dce3ee;border-radius:9px;text-decoration:none;color:#475569;font-size:12px;font-weight:650;white-space:nowrap}
.ai-back-to-tickets:hover{background:#f8fafc;border-color:#b8ceff;color:#2859a8}
@media(max-width:800px){.ai-ticket-list-view{padding:12px}.ai-ticket-list-option{align-items:flex-start;padding:13px;gap:10px}.ai-ticket-list-meta{font-size:10px}.ai-back-to-tickets span{display:none}}


/* Keep response components consistent with the citizen conversation view. */
.ai-response .ai-bubble:has(.ai-component){display:flex;flex-direction:column;align-items:stretch;gap:10px;padding:16px}
.ai-response .ai-component{margin:0;width:100%;}
.ai-component-label{display:block;margin:0 0 7px;color:#475569;font-size:11px;font-weight:650;}
.ai-response-image-wrap{display:flex;flex-direction:column;align-items:flex-start;max-width:100%;}
.ai-response-image-trigger{display:block;max-width:min(100%,360px);padding:0;border:1px solid #dce4f0;border-radius:10px;overflow:hidden;background:#fff;cursor:zoom-in;}
.ai-response-image-trigger img{display:block;width:auto;max-width:100%;max-height:320px;object-fit:contain;}
.ai-response-image-trigger:focus-visible{outline:3px solid #9bb8ff;outline-offset:3px;}
.ai-response-file,.ai-response-link{display:flex;align-items:center;gap:10px;width:100%;box-sizing:border-box;padding:9px 10px;border:1px solid #dce4f0;border-radius:10px;background:#fff;color:#172033;text-decoration:none;overflow-wrap:anywhere;min-height:48px}
.ai-response-resource-icon{display:flex;align-items:center;justify-content:center;flex:0 0 34px;width:34px;height:34px;border-radius:9px;background:#edf2ff;color:#4169e1;font-size:16px}
.ai-response-resource-copy{display:flex;flex:1;min-width:0;flex-direction:column;gap:3px}
.ai-response-resource-title{font-size:12px;font-weight:600;overflow-wrap:anywhere}
.ai-response-resource-detail{font-size:10px;color:#64748b;overflow-wrap:anywhere}
.ai-response-resource-action{flex:0 0 auto;color:#64748b;font-size:14px}
.ai-response-file:hover,.ai-response-link:hover{border-color:#b7caff;background:#fbfcff}
.ai-response-qr{display:flex;flex-direction:column;align-items:center;text-align:center;gap:8px;padding-top:10px;border-top:1px solid #e3e8f2;}
.ai-response-qr .ai-component-label{width:100%;text-align:center;margin:0;}
.ai-response-qr .response-qr-code{display:flex;align-items:center;justify-content:center;width:176px;height:176px;max-width:100%;padding:8px;box-sizing:border-box;border:1px solid #e1e7f1;border-radius:11px;background:#fff;}
.ai-response-qr .response-qr-code svg{display:block;width:100%;height:100%;}
.ai-response-qr>a{font-size:12px;}
.ai-image-lightbox{position:fixed;inset:0;z-index:9999;display:none;align-items:center;justify-content:center;padding:24px;background:rgba(15,23,42,.82);}
.ai-image-lightbox.is-open{display:flex;}
.ai-image-lightbox img{display:block;max-width:min(94vw,1400px);max-height:88dvh;object-fit:contain;border-radius:8px;background:#fff;}
.ai-image-lightbox-close{position:absolute;top:18px;right:20px;width:42px;height:42px;border:0;border-radius:50%;background:#fff;color:#172033;font-size:24px;cursor:pointer;}
@media(max-width:800px){.ai-response .ai-bubble:has(.ai-component){width:88%;}.ai-response-image-trigger{max-width:100%;}.ai-response-image-trigger img{max-height:260px;}}

/* Mobile inbox behavior mirrors My Inquiries: show the conversation list first,
   then switch to a full-width ticket view after a citizen is selected. */
.ai-mobile-inbox-back{display:none;align-items:center;justify-content:center;gap:5px;flex:0 0 auto;min-height:34px;padding:0 9px;border:1px solid #dce3ee;border-radius:9px;background:#fff;color:#475569;text-decoration:none;font-size:11px;font-weight:650}
.ai-mobile-inbox-back i{font-size:15px}
.ai-chat-head-copy{min-width:0;flex:1}
.ai-chat-head-copy h2,.ai-chat-head-copy p{overflow-wrap:anywhere}
@media(max-width:640px){
  .agency-inbox{display:flex;flex-direction:column;height:calc(100dvh - 148px);min-height:0;max-height:none;border-radius:13px}
  .ai-sidebar{display:flex;flex:1 1 auto;width:100%;min-width:0;min-height:0;border-right:0}
  .ai-sidebar-head{padding:14px 13px 12px}
  .ai-sidebar-head h2{font-size:20px}
  .ai-list{padding:0 8px 10px}
  .ai-item{padding:12px 10px}
  .ai-avatar{width:38px;height:38px;flex-basis:38px}
  .ai-item-name{font-size:12px}
  .ai-preview{margin:7px 0 0 48px}
  .ai-status{margin:6px 0 0 48px}
  .ai-main{display:none;width:100%;height:100%;min-height:0;flex:1 1 auto}
  .agency-inbox.ai-mobile-show-conversation .ai-sidebar{display:none}
  .agency-inbox.ai-mobile-show-conversation .ai-main{display:flex}
  .ai-chat-head{flex:0 0 auto;padding:10px 11px;gap:8px}
  .ai-mobile-inbox-back{display:inline-flex}
  .ai-chat-head .ai-back-to-tickets.ai-back-icon{display:none}
  .ai-chat-head .ai-avatar{width:34px;height:34px;flex-basis:34px}
  .ai-chat-head h2{font-size:13px;line-height:1.35}
  .ai-chat-head p{font-size:10px;line-height:1.4}
  .ai-ticket-list-view{padding:12px;overflow:auto}
  .ai-ticket-list-option{align-items:flex-start;padding:12px;gap:8px}
  .ai-ticket-list-meta{font-size:10px}
  .ai-messages{padding:14px 10px;overscroll-behavior:contain}
  .ai-compose{padding:10px}
  .ai-compose-title{font-size:11px}
  .ai-compose-row{grid-template-columns:minmax(0,1fr) 40px;gap:7px}
  .ai-compose-row select{min-height:40px}
  .ai-compose-field textarea{font-size:16px;min-height:42px}
  .ai-compose-actions{gap:8px}
  .ai-compose-note{line-height:1.45}
}
@media(max-width:380px){
  .agency-inbox{height:calc(100dvh - 140px)}
  .ai-chat-head{gap:6px;padding:9px}
  .ai-mobile-inbox-back{padding:0 7px;font-size:10px}
  .ai-chat-head .ai-avatar{width:30px;height:30px;flex-basis:30px}
}

/* Mark only the individual trashed ticket; never tint the citizen conversation row. */
.ai-ticket-list-option.is-trashed{background:#fff7ed;border-color:#fed7aa}
.ai-ticket-list-option.is-trashed .ai-ticket-list-meta{color:#9a3412}
.ai-trashed-badge{display:inline-flex;align-items:center;gap:5px;margin:8px 0 0 50px;padding:4px 8px;border-radius:999px;background:#ffedd5;color:#9a3412;font-size:10px;font-weight:750}
.ai-ticket-list-option.is-trashed .ai-ticket-list-main strong{color:#9a3412}
.ai-chat-head-copy{flex:1;min-width:0}
.ai-chat-head-actions{display:flex;align-items:center;gap:7px;flex:0 0 auto;margin-left:auto}
.ai-trash-ticket-button{background:#fff1f2;color:#be123c;border:1px solid #fecdd3;width:36px;height:36px}
.ai-trash-ticket-button:hover{background:#ffe4e6;color:#9f1239}
.ai-ticket-actions{position:relative;flex:0 0 auto}
.ai-ticket-actions summary{list-style:none;cursor:pointer}
.ai-ticket-actions summary::-webkit-details-marker{display:none}
.ai-ticket-actions-menu{position:absolute;right:0;top:calc(100% + 7px);z-index:30;min-width:205px;padding:6px;border:1px solid #e2e8f0;border-radius:12px;background:#fff;box-shadow:0 12px 35px #0f172a20}
.ai-ticket-actions-menu form{margin:0}
.ai-ticket-actions-menu button{display:flex;align-items:center;gap:9px;width:100%;padding:10px;border:0;border-radius:8px;background:transparent;text-align:left;font:inherit;font-size:12px;color:#334155;cursor:pointer}
.ai-ticket-actions-menu button:hover{background:#f1f5f9}
.ai-ticket-actions-menu .is-danger{color:#be123c}
.ai-trashed-banner{display:flex;gap:11px;align-items:flex-start;margin:0 auto 16px;max-width:1000px;padding:13px 15px;border:1px solid #fdba74;border-radius:12px;background:#fff7ed;color:#9a3412;font-size:12px;line-height:1.5}
.ai-trashed-banner>i{font-size:20px;flex:0 0 auto}
.ai-trashed-banner strong{display:block;margin-bottom:3px}
.ai-trashed-banner p{margin:0;white-space:pre-wrap;overflow-wrap:anywhere}
.ai-trashed-banner small{display:block;margin-top:5px;color:#a16207}
.ai-ticket-modal-backdrop[hidden]{display:none!important}
.ai-ticket-modal-backdrop{position:fixed;inset:0;z-index:10000;display:flex;align-items:center;justify-content:center;padding:18px;background:rgba(15,23,42,.52)}
.ai-ticket-modal{width:min(100%,480px);padding:22px;border:1px solid #e2e8f0;border-radius:18px;background:#fff;box-shadow:0 24px 70px #0f172a35;color:#172033}
.ai-ticket-modal h2{margin:0 0 7px;font-size:18px}
.ai-ticket-modal p{margin:0 0 16px;color:#64748b;font-size:12px;line-height:1.55}
.ai-ticket-modal label{display:block;margin-bottom:7px;font-size:12px;font-weight:750}
.ai-ticket-modal textarea{display:block;width:100%;min-height:118px;box-sizing:border-box;padding:11px 12px;border:1px solid #cbd5e1;border-radius:11px;font:inherit;font-size:13px;resize:vertical}
.ai-ticket-modal textarea:focus{outline:3px solid #dbeafe;border-color:#60a5fa}
.ai-ticket-modal-error{margin:8px 0;color:#b91c1c;font-size:12px}
.ai-ticket-modal-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:16px}
.ai-ticket-modal-actions button{min-height:38px;padding:8px 13px;border:1px solid #dbe2ec;border-radius:10px;background:#fff;color:#334155;font:inherit;font-size:12px;font-weight:700;cursor:pointer}
.ai-ticket-modal-actions .confirm{border-color:#dc2626;background:#dc2626;color:#fff}
.ai-flash{margin:0 0 12px;padding:11px 14px;border-radius:10px;font-size:12px}
.ai-flash.success{background:#ecfdf5;color:#166534;border:1px solid #bbf7d0}
.ai-flash.error{background:#fff1f2;color:#9f1239;border:1px solid #fecdd3}
@media(max-width:640px){.ai-chat-head-actions{gap:5px}.ai-trash-ticket-button{width:34px;height:34px}.ai-trashed-banner{margin-bottom:12px}.ai-ticket-modal{padding:18px}.ai-ticket-actions-menu{position:fixed;right:14px;top:auto;bottom:16px;min-width:min(250px,calc(100vw - 28px))}}


/* Fill the available workspace instead of leaving a large blank strip below the inbox. */
@media(min-width:641px){.agency-inbox{height:calc(100dvh - 125px);min-height:480px;max-height:1000px}}
.ai-compose-modern{padding:10px 16px 12px;flex:0 0 auto}
.ai-compose-modern .ai-compose-title{margin:0 0 8px;font-size:11px;color:#475569}
.ai-compose-modern .ai-compose-title span{font-weight:500;color:#64748b}
.ai-chat-composer{display:flex;align-items:flex-end;gap:8px;padding:7px 7px 7px 15px;border:1px solid #dce4f0;border-radius:17px;background:#fff;box-shadow:0 2px 8px rgba(28,55,105,.06);transition:border-color .15s,box-shadow .15s}
.ai-chat-composer:focus-within{border-color:#9db7ff;box-shadow:0 0 0 3px #eaf1ff}
.ai-chat-composer textarea{display:block;flex:1;min-width:0;max-height:140px;min-height:38px;resize:none;overflow-y:auto;padding:9px 0;border:0;outline:0;background:transparent;color:#172033;font:inherit;font-size:13px;line-height:1.45}
.ai-chat-composer textarea::placeholder{color:#94a3b8}
.ai-chat-composer .ai-composer-send{width:38px;height:38px;flex:0 0 38px;border-radius:12px}
.ai-attachment-chips{display:flex;flex-wrap:wrap;gap:7px;margin-top:7px}.ai-attachment-chips[hidden]{display:none}
.ai-attachment-chip{display:inline-flex;align-items:center;gap:8px;max-width:100%;padding:6px 8px 6px 10px;border:1px solid #dce5f4;border-radius:10px;background:#f8faff;color:#334155;font-size:11px}
.ai-attachment-chip>i{color:#4169e1;font-size:15px;flex:0 0 auto}
.ai-attachment-chip-label{max-width:min(42vw,300px);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.ai-attachment-chip-remove{display:grid;place-items:center;width:23px;height:23px;flex:0 0 23px;border:0;border-radius:7px;background:#eef2f8;color:#64748b;cursor:pointer}
.ai-attachment-chip-remove:hover{background:#fee2e2;color:#b91c1c}
.ai-compose-toolbar{margin-top:8px;display:flex;align-items:center;justify-content:space-between;gap:10px}
.ai-attach-actions{display:flex;align-items:center;gap:5px;flex-wrap:wrap}
.ai-attach-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;min-height:32px;padding:0 10px;border:1px solid transparent;border-radius:9px;background:#f8fafc;color:#475569;font:inherit;font-size:11px;cursor:pointer}
.ai-attach-btn i{font-size:15px;color:#4169e1}.ai-attach-btn:hover{background:#edf3ff;border-color:#dbe6ff;color:#244ca8}
.ai-compose-modern .ai-compose-note{font-size:10px;text-align:right}
.ai-link-modal{position:fixed;inset:0;z-index:10000;display:flex;align-items:center;justify-content:center;padding:18px;background:rgba(15,23,42,.48);backdrop-filter:blur(3px)}
.ai-link-modal[hidden]{display:none}
.ai-link-dialog{width:min(100%,430px);padding:22px;border:1px solid #e2e8f0;border-radius:18px;background:#fff;box-shadow:0 24px 80px rgba(15,23,42,.22)}
.ai-link-dialog-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:18px}.ai-link-dialog-head h2{margin:0;font-size:17px;color:#172033}.ai-link-dialog-head p{margin:5px 0 0;font-size:12px;line-height:1.5;color:#64748b}
.ai-link-close{width:30px;height:30px;border:0;border-radius:9px;background:#f1f5f9;color:#64748b;font-size:22px;cursor:pointer}
.ai-link-dialog label{display:block;margin:13px 0 6px;font-size:11px;font-weight:750;color:#334155}.ai-link-dialog label span{font-weight:400;color:#94a3b8}
.ai-link-dialog input{display:block;width:100%;box-sizing:border-box;min-height:42px;padding:10px 12px;border:1px solid #dce3ee;border-radius:10px;outline:0;font:inherit;font-size:13px;color:#172033}.ai-link-dialog input:focus{border-color:#8eafff;box-shadow:0 0 0 3px #eaf1ff}
.ai-link-error{margin:8px 0 0;color:#b91c1c;font-size:11px}.ai-link-dialog-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:20px}.ai-link-cancel,.ai-link-done{min-height:38px;padding:0 14px;border:1px solid #dce3ee;border-radius:10px;background:#fff;color:#475569;font:inherit;font-size:12px;font-weight:650;cursor:pointer}.ai-link-done{border-color:#2861e8;background:#2861e8;color:#fff}.ai-link-done:hover{background:#1e4fd0}
@media(max-width:640px){.agency-inbox{height:calc(100dvh - 112px);min-height:0}.ai-compose-modern{padding:8px 9px max(9px,env(safe-area-inset-bottom))}.ai-compose-modern .ai-compose-title{font-size:10px}.ai-chat-composer{padding:5px 5px 5px 12px;border-radius:15px}.ai-chat-composer textarea{font-size:16px;min-height:36px;padding:8px 0}.ai-chat-composer .ai-composer-send{width:36px;height:36px;flex-basis:36px}.ai-compose-toolbar{align-items:flex-start;flex-direction:column;gap:6px}.ai-compose-modern .ai-compose-note{text-align:left;font-size:10px}.ai-attach-btn{min-height:31px;padding:0 9px}.ai-attachment-chip-label{max-width:58vw}.ai-link-dialog{padding:18px;border-radius:15px}}


/* Compact image gallery: multiple screenshots share the bubble width instead of stacking full-size. */
.ai-image-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,105px),1fr));gap:7px;width:100%;align-items:start}
.ai-response .ai-image-grid .ai-component-image{min-width:0;margin:0;width:auto}
.ai-image-grid .ai-component-label{font-size:10px;line-height:1.25;max-width:100%;overflow-wrap:anywhere}
.ai-image-grid .ai-response-image-wrap{width:100%;min-width:0}
.ai-image-grid .ai-response-image-trigger{width:100%;max-width:100%;height:104px;display:flex;align-items:center;justify-content:center}
.ai-image-grid .ai-response-image-trigger img{width:100%;height:100%;max-width:100%;max-height:104px;object-fit:contain}
@media(max-width:800px){.ai-image-grid{grid-template-columns:repeat(auto-fit,minmax(min(100%,88px),1fr));gap:6px}.ai-image-grid .ai-response-image-trigger{height:88px}.ai-image-grid .ai-response-image-trigger img{max-height:88px}}

/* Natural-width image attachments: let each preview occupy only its rendered image width. */
.ai-image-grid {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: flex-start;
    gap: 8px;
    width: fit-content;
    max-width: 100%;
}
.ai-response .ai-image-grid .ai-component-image {
    display: flex;
    flex: 0 1 auto;
    flex-direction: column;
    align-items: flex-start;
    width: fit-content;
    max-width: min(100%, 220px);
    min-width: 0;
    margin: 0;
}
.ai-image-grid .ai-component-label {
    display: block;
    width: fit-content;
    max-width: min(100%, 220px);
    overflow-wrap: anywhere;
}
.ai-image-grid .ai-response-image-wrap {
    display: flex;
    width: fit-content;
    max-width: min(100%, 220px);
    min-width: 0;
}
.ai-image-grid .ai-response-image-trigger {
    display: inline-flex;
    flex: 0 1 auto;
    align-items: center;
    justify-content: center;
    width: fit-content;
    height: auto;
    max-width: min(100%, 220px);
    max-height: 104px;
    padding: 0;
    box-sizing: border-box;
}
.ai-image-grid .ai-response-image-trigger img {
    display: block;
    width: auto;
    height: auto;
    max-width: 100%;
    max-height: 104px;
    object-fit: contain;
}
@media (max-width: 800px) {
    .ai-image-grid { gap: 6px; }
    .ai-image-grid .ai-component-image,
    .ai-image-grid .ai-component-label,
    .ai-image-grid .ai-response-image-wrap,
    .ai-image-grid .ai-response-image-trigger { max-width: min(100%, 180px); }
    .ai-image-grid .ai-response-image-trigger img { max-height: 88px; }
}


.ai-faq-convert{position:relative;flex:0 0 auto}
.ai-faq-convert>summary{list-style:none;display:inline-flex;align-items:center;gap:7px;padding:9px 11px;border:1px solid #cbdcff;border-radius:10px;background:#eff5ff;color:#2859a8;font-size:12px;font-weight:750;cursor:pointer;white-space:nowrap}
.ai-faq-convert>summary::-webkit-details-marker{display:none}
.ai-faq-convert[open]>summary{background:#e4edff}
.ai-faq-convert-panel{position:absolute;right:0;top:calc(100% + 8px);z-index:50;width:min(340px,calc(100vw - 32px));padding:14px;border:1px solid #dce3ee;border-radius:13px;background:#fff;box-shadow:0 16px 40px #17203324}
.ai-faq-convert-panel label{display:block;margin-bottom:7px;font-size:11px;font-weight:750;color:#475569}
.ai-faq-convert-panel select{display:block;width:100%;box-sizing:border-box;padding:10px;border:1px solid #dce3ee;border-radius:9px;background:#fff;color:#172033;font:inherit;font-size:12px}
.ai-faq-convert-panel p{margin:8px 0 12px;color:#64748b;font-size:11px;line-height:1.5}
.ai-faq-convert-submit{display:flex;justify-content:center;width:100%;padding:10px;border:0;border-radius:9px;background:#2861e8;color:#fff;font:inherit;font-size:12px;font-weight:750;cursor:pointer}
.ai-faq-convert-submit:hover{background:#1e4fd0}
@media(max-width:640px){.ai-faq-convert-panel{right:-42px}}

.ai-faq-open-modal{display:inline-flex;align-items:center;gap:7px;padding:9px 11px;border:1px solid #cbdcff;border-radius:10px;background:#eff5ff;color:#2859a8;font-size:12px;font-weight:750;cursor:pointer;white-space:nowrap}
.ai-faq-open-modal:hover{background:#e4edff}
.ai-faq-modal{display:none;position:fixed;inset:0;z-index:10000;align-items:center;justify-content:center;padding:20px;box-sizing:border-box}
.ai-faq-modal.is-open{display:flex}.ai-faq-modal-backdrop{position:absolute;inset:0;background:rgba(15,23,42,.58);backdrop-filter:blur(3px)}
.ai-faq-modal-dialog{position:relative;display:flex;flex-direction:column;width:min(620px,100%);max-height:min(88vh,850px);background:#fff;border:1px solid #e2e8f0;border-radius:18px;box-shadow:0 24px 80px #0f172a40;overflow:hidden}
.ai-faq-modal-header{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:20px 22px;border-bottom:1px solid #e8edf4}
.ai-faq-modal-eyebrow{font-size:10px;font-weight:800;letter-spacing:.1em;color:#64748b}.ai-faq-modal-header h2{margin:5px 0 0;font-size:19px;line-height:1.3;color:#172033}
.ai-faq-modal-close{display:grid;place-items:center;flex:0 0 36px;width:36px;height:36px;border:1px solid #e2e8f0;border-radius:10px;background:#fff;color:#475569;cursor:pointer}
.ai-faq-modal-body{padding:20px 22px;overflow:auto}.ai-faq-question-card{padding:14px 16px;border:1px solid #dbeafe;border-radius:12px;background:#f5f9ff;margin-bottom:20px}
.ai-faq-question-card label{display:block;margin-bottom:7px;color:#4264a0;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.06em}
.ai-faq-question-card p{margin:0;color:#1e293b;font-size:13px;line-height:1.65;white-space:pre-wrap;overflow-wrap:anywhere}
.ai-faq-modal .ai-faq-convert-panel{position:static;inset:auto;width:100%;max-width:none;padding:0;border:0;border-radius:0;background:transparent;box-shadow:none}.ai-faq-modal-form>label{display:block;margin-bottom:8px;color:#334155;font-size:12px;font-weight:750}.ai-faq-modal-form select{display:block;width:100%;box-sizing:border-box;padding:11px 12px;border:1px solid #cbd5e1;border-radius:10px;background:#fff;color:#172033;font:inherit;font-size:13px}
.ai-faq-response-preview{margin-top:16px;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden}.ai-faq-preview-heading{display:flex;align-items:center;gap:8px;padding:11px 13px;background:#f8fafc;border-bottom:1px solid #e2e8f0;color:#334155;font-size:12px;font-weight:800}
.ai-faq-preview-content{display:flex;flex-direction:column;gap:12px;padding:14px;min-height:60px}.ai-faq-preview-attachments{display:flex;flex-wrap:wrap;gap:8px}.ai-faq-preview-text{margin:0;color:#334155;font-size:13px;line-height:1.7;white-space:pre-wrap;overflow-wrap:anywhere}.ai-faq-preview-empty{margin:0;color:#94a3b8;font-size:12px;line-height:1.6}
.ai-faq-preview-attachment{position:relative;display:flex;align-items:center;gap:9px;max-width:100%;min-width:0;padding:8px 34px 8px 10px;border:1px solid #dbe3ee;border-radius:10px;background:#f8fafc;color:#334155;font-size:11px}
.ai-faq-preview-attachment img{width:42px;height:42px;object-fit:cover;border-radius:6px;background:#e2e8f0}.ai-faq-preview-file-icon{display:grid;place-items:center;width:36px;height:36px;border-radius:7px;background:#e8efff;color:#315fc1;font-size:18px;flex:0 0 auto}
.ai-faq-preview-attachment-name{overflow-wrap:anywhere;min-width:0}.ai-faq-preview-attachment-remove{position:absolute;right:6px;top:6px;z-index:2;display:grid;place-items:center;width:21px;height:21px;border:0;border-radius:6px;background:#e2e8f0;color:#475569;cursor:pointer}
.ai-faq-modal-hint{margin:12px 0 0;color:#64748b;font-size:11px;line-height:1.55}.ai-faq-modal-footer{display:flex;justify-content:flex-end;align-items:center;gap:10px;margin-top:18px}
.ai-faq-modal-cancel{padding:10px 14px;border:1px solid #cbd5e1;border-radius:9px;background:#fff;color:#475569;font-size:12px;font-weight:750;cursor:pointer}.ai-faq-modal-footer .ai-faq-convert-submit{width:auto;padding:10px 14px}
@media(max-width:600px){.ai-faq-modal{padding:10px;align-items:flex-end}.ai-faq-modal-dialog{max-height:92vh;border-radius:16px}.ai-faq-modal-header{padding:16px}.ai-faq-modal-body{padding:15px 16px}.ai-faq-modal-header h2{font-size:17px}.ai-faq-modal-footer{position:sticky;bottom:0;background:#fff;padding-top:10px}.ai-faq-modal-footer button{flex:1}}

/* When an agency is selected, the agency header is the only header shown in the main panel. */\n.agency-inbox.has-selected-agency .ai-user-selection-head{display:none!important}\n</style>
@endpush
@section('content')
@if(session('success'))<div class="ai-flash success" role="status">{{ session('success') }}</div>@endif
@if(session('error'))<div class="ai-flash error" role="alert">{{ session('error') }}</div>@endif
<div class="agency-inbox {{ $selectedUser ? 'ai-mobile-show-conversation' : 'ai-mobile-show-list' }} {{ $selectedAgencyId ? 'has-selected-agency' : '' }}" data-agency-inbox data-agency-id="{{ (int) ($agency->id ?? 0) }}" data-selected-ticket-id="{{ (int) ($selectedTicket->id ?? 0) }}">
    <aside class="ai-sidebar">
        <div class="ai-sidebar-head"><h2>User Tickets</h2><p>{{ $conversations->count() }} citizen conversation{{ $conversations->count() === 1 ? '' : 's' }}</p>
            <form method="GET" action="{{ route('admin.support.requests') }}"><input class="ai-search" type="search" name="search" value="{{ $search }}" placeholder="Search citizens or tickets..." aria-label="Search citizens or tickets"></form>
        </div>
        <nav class="ai-list" aria-label="Citizen conversations">
        @forelse($conversations as $conversation)
            @php($person = $conversation->first()?->user)
            @php($latest = $conversation->first())
            @php($personName = trim(($person?->first_name ?? '').' '.($person?->last_name ?? '')) ?: 'Citizen')
            <a class="ai-item {{ $selectedUser && (int)$selectedUser->id === (int)$person?->id ? 'active' : '' }}" href="{{ route('admin.support.requests', ['user' => $person?->id, 'search' => $search ?: null]) }}">
                <div class="ai-item-top"><span class="ai-avatar">{{ strtoupper(substr($person?->first_name ?? 'C',0,1).substr($person?->last_name ?? '',0,1)) }}</span><span class="ai-item-name">{{ $personName }}</span><span class="ai-item-date">{{ ($latest?->trashed() ? $latest?->created_at : $latest?->updated_at)?->format('M j') }}</span>@if($conversation->contains(fn ($ticket) => !$ticket->trashed() && (!$ticket->agency_seen_at || $ticket->updated_at > $ticket->agency_seen_at)))<span class="ai-unread-dot" role="img" aria-label="New ticket activity" title="New ticket activity"></span>@endif</div>
                <div class="ai-preview">{{ '#'.$latest?->id }} · {{ $latest?->trashed() ? ($latest?->user_deleted_at ? 'Trashed by citizen' : 'Trashed by administration') : \Illuminate\Support\Str::limit($latest?->question ?? 'No ticket details', 55) }}</div>
                <span class="ai-status">{{ $conversation->count() }} ticket{{ $conversation->count() === 1 ? '' : 's' }} · {{ $latest?->trashed() ? ($latest?->user_deleted_at ? 'Trashed by citizen' : 'Trashed by administration') : str_replace('_',' ',ucfirst($latest?->status ?? 'pending')) }}</span>
            </a>
        @empty <div class="ai-empty">No support requests found.</div> @endforelse
        </nav>
    </aside>
    <section class="ai-main" aria-label="Citizen tickets and response">
    @if($selectedUser && !$selectedTicket)
        @php($selectedUserName = trim(($selectedUser->first_name ?? '').' '.($selectedUser->last_name ?? '')) ?: 'Citizen')
        @if(!$selectedAgencyId)
        <header class="ai-chat-head ai-user-selection-head"><a class="ai-mobile-inbox-back" href="{{ route('admin.support.requests', ['search' => $search ?: null]) }}" aria-label="Back to conversations"><i class="ph ph-arrow-left" aria-hidden="true"></i><span>Inbox</span></a><span class="ai-avatar">{{ strtoupper(substr($selectedUser->first_name ?? 'C',0,1).substr($selectedUser->last_name ?? '',0,1)) }}</span><div class="ai-chat-head-copy"><h2>{{ $selectedUserName }}</h2><p>Select an agency to view this citizen’s tickets</p></div></header>
        <div class="ai-ticket-list-view"><p class="ai-ticket-picker-label">Agencies contacted by this citizen</p><div class="ai-ticket-list">
        @forelse($selectedAgencies as $agencyGroup)
          <a class="ai-ticket-list-option" href="{{ route('admin.support.requests', ['user'=>$selectedUser->id,'agency'=>$agencyGroup->agency->id,'search'=>$search ?: null]) }}"><span class="ai-ticket-list-main"><strong>{{ $agencyGroup->agency->agency_name }}</strong><span>{{ $agencyGroup->tickets->count() }} ticket{{ $agencyGroup->tickets->count()===1?'':'s' }} · Latest: {{ \Illuminate\Support\Str::limit($agencyGroup->tickets->first()->question, 85) }}</span></span><span class="ai-ticket-list-meta">{{ $agencyGroup->tickets->first()->created_at?->format('M j, Y') }} <i class="ph ph-caret-right" aria-hidden="true"></i></span></a>
        @empty <div class="ai-empty">This citizen has no tickets associated with an agency.</div> @endforelse
        </div></div>
        @else
        <header class="ai-chat-head"><a class="ai-back-to-tickets ai-back-icon" href="{{ route('admin.support.requests',['user'=>$selectedUser->id,'search'=>$search ?: null]) }}" aria-label="Back to agencies" title="Back to agencies"><i class="ph ph-arrow-left" aria-hidden="true"></i></a><span class="ai-avatar">{{ strtoupper(substr($agency->agency_name ?? 'A',0,2)) }}</span><div class="ai-chat-head-copy"><h2>{{ $agency->agency_name }}</h2><p>{{ $selectedTickets->count() }} ticket{{ $selectedTickets->count()===1?'':'s' }} from this citizen · Select a ticket to open its conversation</p></div></header>
        <div class="ai-ticket-list-view"><p class="ai-ticket-picker-label">Tickets sent to {{ $agency->agency_name }}</p>
            <div class="ai-ticket-list">
            @forelse($selectedTickets as $ticketOption)
                <a class="ai-ticket-list-option {{ $ticketOption->trashed() ? 'is-trashed' : '' }}" href="{{ route('admin.support.requests', ['user' => $selectedUser->id, 'agency' => $ticketOption->agency_id, 'ticket' => $ticketOption->id, 'search' => $search ?: null]) }}">
                    <span class="ai-ticket-list-main"><strong>Ticket #{{ $ticketOption->id }} · {{ $ticketOption->trashed() ? ($ticketOption->user_deleted_at ? 'Trashed by citizen' : 'Trashed by administration') : str_replace('_',' ',ucfirst($ticketOption->status)) }}</strong><span>{{ $ticketOption->question }}</span></span>
                    <span class="ai-ticket-list-meta">@if(!$ticketOption->trashed() && (!$ticketOption->agency_seen_at || $ticketOption->updated_at > $ticketOption->agency_seen_at))<span class="ai-unread-dot" role="img" aria-label="New ticket activity" title="New ticket activity"></span>@endif {{ $ticketOption->created_at?->format('M j, Y · g:i A') }} <i class="ph ph-caret-right" aria-hidden="true"></i></span>
                </a>
            @empty
                <div class="ai-empty">This citizen has no available tickets.</div>
            @endforelse
            </div>
        </div>
        @endif
    @elseif($selectedUser && $selectedTicket)
        @php($selectedUserName = trim(($selectedUser->first_name ?? '').' '.($selectedUser->last_name ?? '')) ?: 'Citizen')
        <header class="ai-chat-head">
            <a class="ai-mobile-inbox-back" href="{{ route('admin.support.requests', ['search' => $search ?: null]) }}" aria-label="Back to conversations"><i class="ph ph-arrow-left" aria-hidden="true"></i><span>Inbox</span></a>
            <a class="ai-back-to-tickets ai-back-icon" href="{{ route('admin.support.requests', ['user' => $selectedUser->id, 'agency' => $selectedAgencyId ?: null, 'search' => $search ?: null]) }}" aria-label="Back to all tickets" title="Back to all tickets"><i class="ph ph-arrow-left" aria-hidden="true"></i></a>
            <span class="ai-avatar">{{ strtoupper(substr($agency->agency_name ?? 'A',0,2)) }}</span>
            <div class="ai-chat-head-copy"><h2>{{ $agency->agency_name ?? 'Selected agency' }}</h2><p>Ticket #{{ $selectedTicket->id }} · {{ $selectedTicket->trashed() ? ($selectedTicket->user_deleted_at ? 'Trashed by citizen' : 'Trashed by administration') : str_replace('_',' ',ucfirst($selectedTicket->status)) }}</p></div>
            <div class="ai-chat-head-actions">
                @if(in_array(auth()->user()->role, ['admin', 'superadmin'], true))
                @php($faqEligibleResponses = $selectedTicket->responses->where('status', 'accepted')->sortBy('responded_at')->values())
                @php($canAddToFaq = !$selectedTicket->trashed() && $selectedTicket->status === 'answered' && ($faqEligibleResponses->isNotEmpty() || ($selectedTicket->responses->isEmpty() && (filled($selectedTicket->answer) || filled($selectedTicket->answer_image)))))
                <?php if ($canAddToFaq) { ?>
                        <button type="button" class="ai-faq-open-modal" data-faq-modal-open="ai-faq-modal-{{ $selectedTicket->id }}">
                            <i class="ph ph-book-open-text" aria-hidden="true"></i> Add to FAQ
                        </button>
                        <div class="ai-faq-modal" id="ai-faq-modal-{{ $selectedTicket->id }}" aria-hidden="true">
                            <div class="ai-faq-modal-backdrop" data-faq-modal-close></div>
                            <section class="ai-faq-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="ai-faq-modal-title-{{ $selectedTicket->id }}">
                                <header class="ai-faq-modal-header">
                                    <div><span class="ai-faq-modal-eyebrow">SUPPORT TICKET #{{ $selectedTicket->id }}</span><h2 id="ai-faq-modal-title-{{ $selectedTicket->id }}">Add response to FAQ</h2></div>
                                    <button type="button" class="ai-faq-modal-close" data-faq-modal-close aria-label="Close preview"><i class="ph ph-x"></i></button>
                                </header>
                                <div class="ai-faq-modal-body">
                                    <section class="ai-faq-question-card">
                                        <label>Citizen's question</label>
                                        <p>{{ $selectedTicket->question }}</p>
                                    </section>
                                    <form class="ai-faq-convert-panel ai-faq-modal-form" method="GET" action="{{ route('admin.support.toFaq', $selectedTicket->id) }}" data-similar-url="{{ route('admin.support.similarFaqs', $selectedTicket->id) }}" data-csrf-token="{{ csrf_token() }}">
                                        <input type="hidden" name="convert_support" value="{{ $selectedTicket->id }}">
                                        <label for="ai-faq-response-{{ $selectedTicket->id }}">Official response to use</label>
                                        <select id="ai-faq-response-{{ $selectedTicket->id }}" name="response_id" required>
                                            <?php foreach ($faqEligibleResponses as $faqResponse) { ?>
                                                <?php
                                                    $faqPreviewComponents = $faqResponse->components->map(function ($component) use ($selectedTicket, $faqResponse) {
                                                        $item = ['type' => $component->type, 'content' => in_array($component->type, ['text', 'link', 'qr_code'], true) ? $component->content : null, 'label' => $component->label];
                                                        if (in_array($component->type, ['image', 'file'], true)) {
                                                            $item['url'] = route('admin.support.response-attachment', [$selectedTicket->id, $faqResponse->id, $component->id]);
                                                            $item['filename'] = $component->label ?: basename((string) $component->content);
                                                        }
                                                        return $item;
                                                    })->values();
                                                ?>
                                                <option value="{{ $faqResponse->id }}" data-components="{{ json_encode($faqPreviewComponents, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}">Response · {{ ($faqResponse->responded_at ?? $faqResponse->forwarded_at ?? $faqResponse->created_at)?->format('M j, Y · g:i A') }}{{ $faqResponse->admin ? ' · '.trim($faqResponse->admin->first_name.' '.$faqResponse->admin->last_name) : '' }}</option>
                                            <?php } ?>
                                            <?php if ($faqEligibleResponses->isEmpty()) { ?>
                                                <option value="0" data-legacy-answer="{{ e($selectedTicket->answer ?? '') }}" data-legacy-image="{{ $selectedTicket->answer_image ? e(\Illuminate\Support\Facades\Storage::disk('public')->url($selectedTicket->answer_image)) : '' }}">Saved legacy answer</option>
                                            <?php } ?>
                                        </select>
                                        <div class="ai-faq-response-preview" aria-live="polite">
                                            <div class="ai-faq-preview-heading"><i class="ph ph-eye"></i><span>Response preview</span></div>
                                            <div class="ai-faq-preview-content"><p class="ai-faq-preview-empty">Choose a response to preview its text and attachments.</p></div>
                                        </div>
                                        <p class="ai-faq-modal-hint">Review the exact accepted response before preparing the FAQ draft. The original question will prefill the FAQ title.</p>
                                        <footer class="ai-faq-modal-footer">
                                            <button type="button" class="ai-faq-modal-cancel" data-faq-modal-close>Cancel</button>
                                            <button class="ai-faq-convert-submit" type="submit">Prepare FAQ draft <i class="ph ph-arrow-right"></i></button>
                                        </footer>
                                    </form>
                                </div>
                            </section>
                        </div>
                <?php } ?>
                @if($selectedTicket->trashed())
                    @if(auth()->user()->role === 'superadmin')
                    <details class="ai-ticket-actions">
                        <summary class="ai-icon-btn" aria-label="Ticket actions" title="Ticket actions"><i class="ph ph-dots-three-vertical" aria-hidden="true"></i></summary>
                        <div class="ai-ticket-actions-menu">
                            <form method="POST" action="{{ route('admin.support.restore', $selectedTicket->id) }}">@csrf @method('PATCH')<button type="submit"><i class="ph ph-arrow-counter-clockwise" aria-hidden="true"></i> Restore ticket</button></form>
                            <form method="POST" action="{{ route('admin.support.forceDelete', $selectedTicket->id) }}" onsubmit="return confirm('Permanently delete ticket #{{ $selectedTicket->id }}? This cannot be undone and removes its response history and attachments.');">@csrf @method('DELETE')<button class="is-danger" type="submit"><i class="ph ph-trash" aria-hidden="true"></i> Delete permanently</button></form>
                        </div>
                    </details>
                    @else
                    @endif
                @else
                    <button type="button" class="ai-icon-btn ai-trash-ticket-button" data-open-trash-modal aria-label="Move ticket to trash" title="Move ticket to trash"><i class="ph ph-trash" aria-hidden="true"></i></button>
                @endif
            @endif
            </div>
        </header>
        <div class="ai-messages">
            @if($selectedTicket->trashed())
                <section class="ai-trashed-banner" role="status"><i class="ph ph-trash" aria-hidden="true"></i><div><strong>{{ $selectedTicket->user_deleted_at ? 'Trashed by citizen' : 'Trashed by administration' }}</strong><p>{{ $selectedTicket->user_deleted_at ? 'The citizen removed this ticket from their own inquiries.' : ($selectedTicket->trash_reason ?: 'No reason was recorded.') }}</p><small>{{ $selectedTicket->user_deleted_at ? 'Trashed by citizen' : 'Moved to trash by administration' }} {{ ($selectedTicket->user_deleted_at ?: $selectedTicket->deleted_at)?->format('M j, Y · g:i A') }}</small></div></section>
            @endif
            <article class="ai-ticket-intro"><small>Citizen's original question · Ticket #{{ $selectedTicket->id }}</small><h3>{{ $selectedTicket->question }}</h3><p>{{ $selectedTicket->created_at?->format('M j, Y · g:i A') }}</p></article>
            @php($reopenMessageRendered = false)
            @foreach($selectedTicket->responses->sortBy(fn ($response) => $response->forwarded_at ?? $response->created_at) as $response)
                @if(!$reopenMessageRendered && filled(trim((string) $selectedTicket->reopen_message)) && (($selectedTicket->reopen_message_at && (($response->forwarded_at ?? $response->created_at) >= $selectedTicket->reopen_message_at)) || (!$selectedTicket->reopen_message_at && in_array($selectedTicket->status, ['awaiting_confirmation', 'answered'], true) && (string) $response->id === (string) optional($selectedTicket->latestResponse)->id)))
                    <article class="ai-follow-up" aria-label="Citizen's reopening message for ticket #{{ $selectedTicket->id }}">
                        <div class="ai-follow-up-bubble">
                            <div class="ai-follow-up-label">Citizen follow-up · Ticket reopened{{ $selectedTicket->reopen_message_at ? ' · '.$selectedTicket->reopen_message_at->format('M j, Y · g:i A') : '' }}</div>
                            <div style="white-space:pre-wrap;overflow-wrap:anywhere">{{ $selectedTicket->reopen_message }}</div>
                        </div>
                    </article>
                    @php($reopenMessageRendered = true)
                @endif
                <article class="ai-response"><div class="ai-response-meta">Official response · {{ $response->admin ? trim($response->admin->first_name.' '.$response->admin->last_name) : 'Agency administrator' }} · {{ $response->forwarded_at?->format('M j, Y · g:i A') }}</div><div class="ai-bubble">
                @if($response->components->where('type', 'image')->isNotEmpty())<div class="ai-image-grid" aria-label="Response images">@foreach($response->components->where('type', 'image') as $component)
                    @php($imageUrl = route('admin.support.response-attachment',['supportRequestId'=>$selectedTicket->id,'responseId'=>$response->id,'componentId'=>$component->id]))
                    <div class="ai-component ai-component-image">
                        @if($component->label)<span class="ai-component-label">{{ $component->label }}</span>@endif
                        <div class="ai-response-image-wrap"><button type="button" class="ai-response-image-trigger" data-image-url="{{ $imageUrl }}" aria-label="View {{ $component->label ?: 'attached image' }}"><img src="{{ $imageUrl }}" alt="{{ $component->label ?: 'Official response image' }}" loading="lazy"></button></div>
                    </div>
                @endforeach</div>@endif
                @foreach($response->components->where('type', 'text') as $component)
                    <div class="ai-component ai-component-text">{!! nl2br(e($component->content)) !!}</div>
                @endforeach
                <div class="ai-component-list ai-response-resources" aria-label="Response files and links">
                    @foreach($response->components->where('type', 'file') as $component)
                        <a class="ai-response-file is-file" href="{{ route('admin.support.response-attachment',['supportRequestId'=>$selectedTicket->id,'responseId'=>$response->id,'componentId'=>$component->id]) }}" download aria-label="Download {{ $component->label ?: 'attachment' }}"><span class="ai-response-resource-icon"><i class="ph ph-file-arrow-down" aria-hidden="true"></i></span><span class="ai-response-resource-copy"><span class="ai-response-resource-title">{{ $component->label ?: 'Download attachment' }}</span><span class="ai-response-resource-detail">File attachment</span></span><i class="ph ph-download-simple ai-response-resource-action" aria-hidden="true"></i></a>
                    @endforeach
                    @foreach($response->components->where('type', 'link') as $component)
                        @php($linkScheme = strtolower((string) parse_url((string) $component->content, PHP_URL_SCHEME)))
                        @if(in_array($linkScheme, ['http', 'https'], true))
                            <a class="ai-response-link is-link" href="{{ $component->content }}" target="_blank" rel="noopener noreferrer"><span class="ai-response-resource-icon"><i class="ph ph-globe" aria-hidden="true"></i></span><span class="ai-response-resource-copy"><span class="ai-response-resource-title">{{ $component->label ?: $component->content }}</span><span class="ai-response-resource-detail">{{ parse_url($component->content, PHP_URL_HOST) ?: $component->content }}</span></span><i class="ph ph-arrow-up-right ai-response-resource-action" aria-hidden="true"></i></a>
                        @endif
                    @endforeach
                </div>
                @foreach($response->components->where('type', 'qr_code') as $component)
                    @php($qrScheme = strtolower((string) parse_url((string) $component->content, PHP_URL_SCHEME)))
                    @if(in_array($qrScheme, ['http', 'https'], true))
                        <div class="ai-component ai-response-qr"><div class="ai-component-label"><i class="ph ph-qr-code" aria-hidden="true"></i> {{ $component->label ?: 'QR code' }}</div><div class="response-qr-code" data-qr-value="{{ $component->content }}" role="img" aria-label="{{ $component->label ?: 'Scannable QR code' }}"></div><a href="{{ $component->content }}" target="_blank" rel="noopener noreferrer">Open destination <i class="ph ph-arrow-up-right" aria-hidden="true"></i></a></div>
                    @endif
                @endforeach
                </div></article>
                @if($response->follow_up_reason)
                    <article class="ai-follow-up" aria-label="Citizen follow-up for ticket #{{ $selectedTicket->id }}">
                        <div class="ai-follow-up-bubble">
                            <div class="ai-follow-up-label">Citizen follow-up · {{ $response->responded_at?->format('M j, Y · g:i A') ?? $response->updated_at?->format('M j, Y · g:i A') }}</div>
                            {{ $response->follow_up_reason }}
                        </div>
                    </article>
                @endif
            @endforeach
            @if(!$reopenMessageRendered && filled(trim((string) $selectedTicket->reopen_message)))
                <article class="ai-follow-up" aria-label="Citizen's reopening message for ticket #{{ $selectedTicket->id }}">
                    <div class="ai-follow-up-bubble">
                        <div class="ai-follow-up-label">Citizen follow-up · Ticket reopened{{ $selectedTicket->reopen_message_at ? ' · '.$selectedTicket->reopen_message_at->format('M j, Y · g:i A') : '' }}</div>
                        <div style="white-space:pre-wrap;overflow-wrap:anywhere">{{ $selectedTicket->reopen_message }}</div>
                    </div>
                </article>
            @endif
            @if($selectedTicket->status === 'awaiting_confirmation')<div class="ai-ticket-intro"><strong>Awaiting citizen confirmation</strong><p>The citizen is being asked whether this response resolved their concern or whether follow-up is needed.</p></div>@elseif($selectedTicket->status === 'answered')<div class="ai-ticket-intro"><strong>Ticket resolved</strong><p>The citizen confirmed that the response resolved their concern.</p></div>@endif
        </div>
        @if(!$selectedTicket->trashed() && !in_array($selectedTicket->status,['awaiting_confirmation','answered'],true))
        <form class="ai-compose ai-compose-modern" data-response-form action="{{ route('admin.support.inbox-reply') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="request_id" value="{{ $selectedTicket->id }}">
            <p class="ai-compose-title">Official response <span>· Ticket #{{ $selectedTicket->id }}</span></p>
            <div class="ai-alert" data-form-error hidden></div>
            <input type="hidden" name="components[0][type]" value="text">
            <div class="ai-chat-composer">
                <textarea name="components[0][content]" data-response-text placeholder="Write your response…" aria-label="Official response" rows="1"></textarea>
                <button type="submit" class="ai-icon-btn send ai-composer-send" data-submit-response aria-label="Send official response" title="Send official response"><i class="ph ph-arrow-up" aria-hidden="true"></i></button>
            </div>
            <div class="ai-attachment-chips" data-attachment-chips aria-live="polite"></div>
            <div class="ai-compose-actions ai-compose-toolbar">
                <div class="ai-attach-actions" aria-label="Add attachment">
                    <button type="button" class="ai-attach-btn" data-pick-images><i class="ph ph-image" aria-hidden="true"></i><span>Images</span></button>
                    <button type="button" class="ai-attach-btn" data-pick-files><i class="ph ph-paperclip" aria-hidden="true"></i><span>Files</span></button>
                    <button type="button" class="ai-attach-btn" data-open-link-modal data-link-mode="link"><i class="ph ph-link-simple" aria-hidden="true"></i><span>Link</span></button>
                    <button type="button" class="ai-attach-btn" data-open-link-modal data-link-mode="qr_code"><i class="ph ph-qr-code" aria-hidden="true"></i><span>QR code</span></button>
                </div>
                <span class="ai-compose-note">Sending asks the citizen to confirm or request follow-up.</span>
            </div>
            <input type="file" data-image-picker accept=".jpg,.jpeg,.png,.webp" multiple hidden>
            <input type="file" data-file-picker accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.webp" multiple hidden>
            <div data-attachment-inputs hidden></div>
        </form>
        @elseif(!$selectedTicket->trashed()) <div class="ai-compose"><p class="ai-compose-note">This ticket is not accepting another response right now. The citizen must request follow-up before your agency can reply again.</p></div>@else <div class="ai-compose"><p class="ai-compose-note">This ticket is in the trash. Restore it before sending another response.</p></div>@endif
    @else <div class="ai-empty">Select a citizen from the left to review their tickets.</div>@endif
    </section>
</div>
@if(in_array(auth()->user()->role, ['admin', 'superadmin'], true) && $selectedTicket && !$selectedTicket->trashed())
<div class="ai-ticket-modal-backdrop" data-trash-modal hidden>
    <section class="ai-ticket-modal" role="dialog" aria-modal="true" aria-labelledby="ai-trash-modal-title" aria-describedby="ai-trash-modal-description">
        <h2 id="ai-trash-modal-title">Move ticket #{{ $selectedTicket->id }} to trash?</h2>
        <p id="ai-trash-modal-description">The ticket will remain visible to the citizen with a notice and the reason you provide. You can restore it later from this inbox.</p>
        <form method="POST" action="{{ route('admin.support.delete', $selectedTicket->id) }}" data-trash-ticket-form>
            @csrf
            @method('DELETE')
            <input type="hidden" name="search" value="{{ $search }}">
            <label for="ai-trash-reason">Reason for moving this ticket to trash</label>
            <textarea id="ai-trash-reason" name="reason" required minlength="3" maxlength="1000" placeholder="Explain why this ticket is being moved to trash...">{{ old('reason') }}</textarea>
            @error('reason')<div class="ai-ticket-modal-error" role="alert">{{ $message }}</div>@enderror
            <div class="ai-ticket-modal-actions"><button type="button" data-close-trash-modal>Cancel</button><button class="confirm" type="submit">Move to trash</button></div>
        </form>
    </section>
</div>
@endif
@endsection
<div class="ai-image-lightbox" data-ai-image-lightbox role="dialog" aria-modal="true" aria-label="Image preview"><button type="button" class="ai-image-lightbox-close" data-ai-image-close aria-label="Close image preview">&times;</button><img data-ai-image-preview alt="Expanded attachment preview"></div>
<div class="ai-link-modal" data-link-modal hidden>
  <section class="ai-link-dialog" role="dialog" aria-modal="true" aria-labelledby="ai-link-title">
    <div class="ai-link-dialog-head"><div><h2 id="ai-link-title">Attach a link</h2><p data-link-modal-description>Add a website or online document to this response.</p></div><button type="button" class="ai-link-close" data-close-link-modal aria-label="Close link dialog">&times;</button></div>
    <label for="ai-link-url">URL</label><input id="ai-link-url" type="url" placeholder="https://example.com" autocomplete="url" inputmode="url">
    <label for="ai-link-label">Display name <span>(optional)</span></label><input id="ai-link-label" type="text" maxlength="255" placeholder="e.g. Application requirements">
    <p class="ai-link-error" data-link-error hidden>Please enter a valid HTTP or HTTPS link.</p>
    <div class="ai-link-dialog-actions"><button type="button" class="ai-link-cancel" data-close-link-modal>Cancel</button><button type="button" class="ai-link-done" data-save-link>Done</button></div>
  </section>
</div>

@push('scripts')
@vite('resources/js/echo.js')
<script>
(()=>{
 const inbox=document.querySelector('[data-agency-inbox]');
 if(!inbox)return;
 const agencyId=Number(inbox.dataset.agencyId);
 const selectedTicketId=Number(inbox.dataset.selectedTicketId||0);
 let subscribed=false,attempts=0,timer=null;
 const connect=()=>{
   if(subscribed||!window.Echo||!Number.isSafeInteger(agencyId)||agencyId<=0)return false;
   window.Echo.private(`agency.${agencyId}.support-requests`)
     .listen('.support.request.created',payload=>{
       if(payload && Number(payload.agency_id)===agencyId) window.location.reload();
     })
     .listen('.support.request.updated',payload=>{
       if(!payload)return;
       const ticketId=Number(payload.id);
       const isUserActivity=['created','ticket_created','follow_up_requested','reopened','question_updated'].includes(payload.action);
       if(ticketId===selectedTicketId){
         // Keep the active conversation authoritative and acknowledge the item
         // on its next render. User follow-ups must be visible immediately.
         if(['follow_up_requested','reopened','question_updated','response_created','updated'].includes(payload.action))window.location.reload();
         return;
       }
       // New activity on another ticket should update the unread indicator too.
       // Reload is the reliable fallback because the Blade inbox is the canonical list.
       if(isUserActivity)window.location.reload();
     });
   subscribed=true;
   return true;
 };
 const retry=()=>{if(connect())return;if(++attempts<40)timer=window.setTimeout(retry,500);};
 retry();
 window.addEventListener('knowurlocal:echo-ready',connect);
 window.addEventListener('pagehide',()=>{if(timer)window.clearTimeout(timer);});
})();
</script>
<script>
(()=>{
 const modal=document.querySelector('[data-trash-modal]');
 if(!modal)return;
 const opener=document.querySelector('[data-open-trash-modal]');
 const closer=modal.querySelector('[data-close-trash-modal]');
 const textarea=modal.querySelector('textarea');
 const open=()=>{modal.hidden=false;document.body.style.overflow='hidden';window.setTimeout(()=>textarea?.focus(),20)};
 const close=()=>{modal.hidden=true;document.body.style.overflow='';opener?.focus()};
 opener?.addEventListener('click',open);
 closer?.addEventListener('click',close);
 modal.addEventListener('click',e=>{if(e.target===modal)close()});
 document.addEventListener('keydown',e=>{if(e.key==='Escape'&&!modal.hidden)close()});
 if(@json($errors->has('reason')))open();
 document.querySelectorAll('.ai-ticket-actions').forEach(menu=>document.addEventListener('click',e=>{if(!menu.contains(e.target))menu.open=false}));
})();
</script>
<script src="{{ asset('jsfiles/public_user/qrcode-core.js') }}?v={{ file_exists(public_path('jsfiles/public_user/qrcode-core.js')) ? filemtime(public_path('jsfiles/public_user/qrcode-core.js')) : time() }}"></script>
<script>
(()=>{const modal=document.querySelector('[data-ai-image-lightbox]');if(!modal)return;const image=modal.querySelector('[data-ai-image-preview]'),close=modal.querySelector('[data-ai-image-close]');const hide=()=>{modal.classList.remove('is-open');image.removeAttribute('src');document.body.style.overflow='';};document.addEventListener('click',e=>{const trigger=e.target.closest('.ai-response-image-trigger[data-image-url]');if(trigger){image.src=trigger.dataset.imageUrl;image.alt=trigger.querySelector('img')?.alt||'Expanded attachment preview';modal.classList.add('is-open');document.body.style.overflow='hidden';close.focus();}});close.addEventListener('click',hide);modal.addEventListener('click',e=>{if(e.target===modal)hide();});document.addEventListener('keydown',e=>{if(e.key==='Escape'&&modal.classList.contains('is-open'))hide();});})();
</script>
<script>
(()=>{
  document.querySelectorAll('.response-qr-code[data-qr-value]').forEach(container=>{
    const value=container.dataset.qrValue,Core=window.QRCodeCore;if(typeof Core!=='function')return;
    try{let qr;for(const level of [Core.CorrectLevel?.M,Core.CorrectLevel?.L]){if(level===undefined)continue;try{const candidate=new Core(0,level);candidate.addData(value);candidate.make();qr=candidate;break}catch(e){}}
      if(qr){const size=qr.getModuleCount(),svg=document.createElementNS('http://www.w3.org/2000/svg','svg');svg.setAttribute('viewBox',`0 0 ${size} ${size}`);svg.setAttribute('width','160');svg.setAttribute('height','160');svg.setAttribute('role','img');svg.setAttribute('aria-label','QR code');const bg=document.createElementNS(svg.namespaceURI,'rect');bg.setAttribute('width',size);bg.setAttribute('height',size);bg.setAttribute('fill','#fff');svg.append(bg);for(let y=0;y<size;y++)for(let x=0;x<size;x++)if(qr.isDark(y,x)){const r=document.createElementNS(svg.namespaceURI,'rect');r.setAttribute('x',x);r.setAttribute('y',y);r.setAttribute('width','1');r.setAttribute('height','1');r.setAttribute('fill','#111');svg.append(r)}container.append(svg)}
    }catch(e){}
  });
  const form=document.querySelector('[data-response-form]');if(!form)return;
  const textarea=form.querySelector('[data-response-text]'),chips=form.querySelector('[data-attachment-chips]'),inputStore=form.querySelector('[data-attachment-inputs]');
  const imagePicker=form.querySelector('[data-image-picker]'),filePicker=form.querySelector('[data-file-picker]'),error=form.querySelector('[data-form-error]'),send=form.querySelector('[data-submit-response]');
  const linkModal=document.querySelector('[data-link-modal]'),linkUrl=document.querySelector('#ai-link-url'),linkLabel=document.querySelector('#ai-link-label'),linkError=document.querySelector('[data-link-error]'),linkTitle=document.querySelector('#ai-link-title'),linkDescription=linkModal?.querySelector('[data-link-modal-description]');
  let nextIndex=1,attachmentCount=0,linkMode='link';
  const fit=()=>{textarea.style.height='38px';textarea.style.height=Math.min(textarea.scrollHeight,140)+'px';textarea.style.overflowY=textarea.scrollHeight>140?'auto':'hidden'};
  textarea.addEventListener('input',fit);fit();
  const syncEmpty=()=>{chips.hidden=!chips.children.length};
  const makeChip=(label,kind,remove)=>{
    const chip=document.createElement('span');chip.className='ai-attachment-chip';
    const icon=document.createElement('i');icon.className='ph '+(kind==='image'?'ph-image':kind==='file'?'ph-file-text':kind==='qr_code'?'ph-qr-code':'ph-link-simple');icon.setAttribute('aria-hidden','true');
    const name=document.createElement('span');name.className='ai-attachment-chip-label';name.textContent=label;name.title=label;
    const close=document.createElement('button');close.type='button';close.className='ai-attachment-chip-remove';close.setAttribute('aria-label','Remove '+label);close.title='Remove attachment';close.innerHTML='<i class="ph ph-x" aria-hidden="true"></i>';close.addEventListener('click',()=>{remove();chip.remove();attachmentCount--;syncEmpty()});
    chip.append(icon,name,close);chips.append(chip);syncEmpty();return chip;
  };
  const addFiles=(files,kind)=>{
    const chosen=[...files];if(!chosen.length)return;
    const allowed=10-1-attachmentCount;if(chosen.length>allowed){showError('A response can contain up to 10 items including the message.');}
    chosen.slice(0,Math.max(0,allowed)).forEach(file=>{
      const index=nextIndex++,type=document.createElement('input'),label=document.createElement('input'),fileInput=document.createElement('input');
      type.type='hidden';type.name=`components[${index}][type]`;type.value=kind;
      label.type='hidden';label.name=`components[${index}][label]`;label.value=file.name;
      fileInput.type='file';fileInput.name=`components[${index}][file]`;fileInput.hidden=true;
      try{const dt=new DataTransfer();dt.items.add(file);fileInput.files=dt.files}catch(e){showError('Your browser could not prepare this attachment. Please try selecting it again.');return}
      inputStore.append(type,label,fileInput);attachmentCount++;
      makeChip(file.name,kind,()=>{type.remove();label.remove();fileInput.remove()});
    });
    imagePicker.value='';filePicker.value='';
  };
  const showError=message=>{error.textContent=message;error.hidden=false};
  form.querySelector('[data-pick-images]').addEventListener('click',()=>imagePicker.click());
  form.querySelector('[data-pick-files]').addEventListener('click',()=>filePicker.click());
  imagePicker.addEventListener('change',()=>addFiles(imagePicker.files,'image'));
  filePicker.addEventListener('change',()=>addFiles(filePicker.files,'file'));
  const openLink=(mode='link',trigger=null)=>{linkMode=mode;linkTitle.textContent=mode==='qr_code'?'Attach a QR code':'Attach a link';linkDescription.textContent=mode==='qr_code'?'Create a scannable QR code that opens this destination.':'Add a website or online document to this response.';linkUrl.value='';linkLabel.value='';linkError.textContent='Please enter a valid HTTP or HTTPS link.';linkError.hidden=true;linkModal.hidden=false;linkModal.dataset.trigger=mode;document.body.style.overflow='hidden';setTimeout(()=>linkUrl.focus(),20)};
  const closeLink=()=>{linkModal.hidden=true;linkUrl.value='';linkLabel.value='';linkError.hidden=true;document.body.style.overflow='';form.querySelector(`[data-open-link-modal][data-link-mode="${linkMode}"]`)?.focus()};
  form.querySelectorAll('[data-open-link-modal]').forEach(button=>button.addEventListener('click',()=>openLink(button.dataset.linkMode||'link',button)));
  linkModal.querySelectorAll('[data-close-link-modal]').forEach(button=>button.addEventListener('click',closeLink));
  linkModal.addEventListener('click',e=>{if(e.target===linkModal)closeLink()});
  linkModal.querySelector('[data-save-link]').addEventListener('click',()=>{
    let url;try{url=new URL(linkUrl.value.trim());if(!['http:','https:'].includes(url.protocol))throw Error()}catch{linkError.hidden=false;linkUrl.focus();return}
    if(attachmentCount>=9){linkError.textContent='A response can contain up to 10 items including the message.';linkError.hidden=false;return}
    const index=nextIndex++,type=document.createElement('input'),content=document.createElement('input'),label=document.createElement('input');
    const componentType=linkMode==='qr_code'?'qr_code':'link';
    type.type='hidden';type.name=`components[${index}][type]`;type.value=componentType;content.type='hidden';content.name=`components[${index}][content]`;content.value=url.href;label.type='hidden';label.name=`components[${index}][label]`;label.value=linkLabel.value.trim()||url.hostname;
    inputStore.append(type,content,label);attachmentCount++;
    makeChip(label.value,componentType,()=>{type.remove();content.remove();label.remove()});closeLink();
  });
  document.addEventListener('keydown',e=>{if(e.key==='Escape'&&!linkModal.hidden)closeLink()});
  form.addEventListener('submit',async e=>{
    e.preventDefault();error.hidden=true;
    if(!textarea.value.trim()&&!attachmentCount){showError('Write a response or add at least one attachment.');textarea.focus();return}
    send.disabled=true;send.innerHTML='<i class="ph ph-spinner-gap" aria-hidden="true"></i>';send.setAttribute('aria-label','Sending response');
    const textType=form.querySelector('[name="components[0][type]"]'),omitText=!textarea.value.trim();if(omitText){textarea.disabled=true;textType.disabled=true}
    try{const body=new FormData(form);const response=await fetch(form.action,{method:'POST',headers:{'X-CSRF-TOKEN':form.querySelector('[name=_token]').value,'Accept':'application/json'},body});let data={};try{data=await response.json()}catch(e){}
      if(!response.ok)throw Error(data.message||Object.values(data.errors||{}).flat().join(' ')||'Unable to send the response.');window.location.href=data.redirect;
    }catch(err){textarea.disabled=false;textType.disabled=false;showError(err.message);send.disabled=false;send.innerHTML='<i class="ph ph-arrow-up" aria-hidden="true"></i>';send.setAttribute('aria-label','Send official response')}
  });
})();


// Add-to-FAQ response preview modal. Removing a chip only hides it in the preview;
// it never deletes a saved response or private attachment.
document.querySelectorAll('[data-faq-modal-open]').forEach(trigger => trigger.addEventListener('click', () => {
 const modal=document.getElementById(trigger.dataset.faqModalOpen); if(!modal)return;
 modal.classList.add('is-open'); modal.setAttribute('aria-hidden','false'); document.body.classList.add('ai-faq-modal-active');
 const select=modal.querySelector('select[name="response_id"]'); renderFaqResponsePreview(select); select?.focus();
}));
document.querySelectorAll('.ai-faq-modal [data-faq-modal-close]').forEach(control=>control.addEventListener('click',()=>{
 const modal=control.closest('.ai-faq-modal'); modal?.classList.remove('is-open'); modal?.setAttribute('aria-hidden','true');
 if(!document.querySelector('.ai-faq-modal.is-open'))document.body.classList.remove('ai-faq-modal-active');
}));
document.addEventListener('keydown',event=>{if(event.key!=='Escape')return;document.querySelectorAll('.ai-faq-modal.is-open').forEach(modal=>{modal.classList.remove('is-open');modal.setAttribute('aria-hidden','true')});document.body.classList.remove('ai-faq-modal-active')});
document.querySelectorAll('.ai-faq-modal select[name="response_id"]').forEach(select=>select.addEventListener('change',()=>renderFaqResponsePreview(select)));
function renderFaqResponsePreview(select){
  const modal=select?.closest('.ai-faq-modal'),container=modal?.querySelector('.ai-faq-preview-content'),option=select?.selectedOptions?.[0];if(!container||!option)return;
  container.replaceChildren();let components=[];try{components=JSON.parse(option.dataset.components||'[]')}catch(_){}
  const legacyAnswer=option.dataset.legacyAnswer||'',legacyImage=option.dataset.legacyImage||'';
  if(!components.length&&legacyAnswer.trim()){const p=document.createElement('p');p.className='ai-faq-preview-text';p.textContent=legacyAnswer;container.append(p)}
  if(!components.length&&legacyImage)components.push({type:'image',url:legacyImage,filename:'Saved answer image'});
  components.forEach(component=>{
   if(component.type==='text'&&component.content){const p=document.createElement('p');p.className='ai-faq-preview-text';p.textContent=component.content;container.append(p);return}
   const isLink=component.type==='link',isQr=['qr','qr_code'].includes(component.type);
   if(!['image','file','link','qr','qr_code'].includes(component.type))return;
   const chip=document.createElement('div');chip.className='ai-faq-preview-attachment';
   if(component.type==='image'&&component.url){const img=document.createElement('img');img.src=component.url;img.alt=component.label||component.filename||'Response image';img.loading='lazy';chip.append(img)}
   else{const icon=document.createElement('span');icon.className='ai-faq-preview-file-icon';icon.textContent=isQr?'▦':isLink?'↗':'↗';icon.setAttribute('aria-hidden','true');chip.append(icon)}
   const copy=document.createElement('span');copy.className='ai-faq-preview-attachment-name';
   const name=document.createElement('span');name.textContent=component.label||component.filename||(isQr?'QR link':isLink?'Link':component.type==='image'?'Image attachment':'Attached file');copy.append(name);
   const destinationUrl=(isLink||isQr)?String(component.content||'').trim():'';
   if(destinationUrl){const destination=document.createElement('span');destination.className='ai-faq-preview-attachment-destination';destination.textContent=destinationUrl;destination.style.display='block';destination.style.marginTop='3px';destination.style.fontSize='10px';destination.style.color='#64748b';destination.style.overflowWrap='anywhere';copy.append(destination)}
   chip.append(copy);
   const openUrl=component.url||destinationUrl;
   if(openUrl){try{const parsed=new URL(openUrl,window.location.origin);if(['http:','https:'].includes(parsed.protocol)){const link=document.createElement('a');link.href=parsed.href;link.target='_blank';link.rel='noopener noreferrer';link.setAttribute('aria-label','Open '+(component.label||destinationUrl||component.filename||'attachment'));link.style.position='absolute';link.style.inset='0 28px 0 0';link.style.borderRadius='10px';chip.append(link)}}catch(_){}}
   const remove=document.createElement('button');remove.type='button';remove.className='ai-faq-preview-attachment-remove';remove.textContent='×';remove.setAttribute('aria-label','Hide attachment from preview');remove.title='Hide from preview only';remove.addEventListener('click',()=>chip.remove());chip.append(remove);container.append(chip);
  });
  if(!container.children.length){const empty=document.createElement('p');empty.className='ai-faq-preview-empty';empty.textContent='This response has no text or attachments.';container.append(empty)}
}

// Check this agency's existing FAQs before opening the draft editor.
document.querySelectorAll('.ai-faq-convert-panel').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        if (form.dataset.similarityChecked === 'true') return;
        event.preventDefault();
        const submit = form.querySelector('button[type="submit"]');
        if (submit) { submit.disabled = true; submit.textContent = 'Checking similar FAQs…'; }
        try {
            const response = await fetch(form.dataset.similarUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': form.dataset.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                credentials: 'same-origin'
            });
            const result = await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'Similarity check failed.');
            const matches = Array.isArray(result.matches) ? result.matches : [];
            if (matches.length) {
                const preview = matches.slice(0, 3).map((item, index) => `${index + 1}. ${item.question} (${item.percentage}% similar)`).join('\n');
                const proceed = await new Promise((resolve) => {
                    if (typeof window.showAlertModal === 'function') {
                        window.showAlertModal({
                            title: 'Similar FAQs found',
                            text: `We found existing FAQs that may cover this question:\n\n${preview}\n\nContinue and prepare a new FAQ anyway?`,
                            icon: 'ph-light ph-warning-circle',
                            variant: 'warning',
                            confirmText: 'Continue anyway',
                            cancelText: 'Cancel',
                            showCancel: true,
                            onConfirm: () => { window.closeAlertModal?.(); resolve(true); },
                            onCancel: () => resolve(false)
                        });
                        return;
                    }
                    // Accessible in-app fallback; never fall back to window.confirm.
                    const backdrop = document.createElement('div');
                    backdrop.className = 'similar-faq-message-backdrop';
                    backdrop.style.cssText = 'position:fixed;inset:0;z-index:100000;display:flex;align-items:center;justify-content:center;padding:20px;background:rgba(15,23,42,.58)';
                    const dialog = document.createElement('section');
                    dialog.setAttribute('role', 'alertdialog'); dialog.setAttribute('aria-modal', 'true');
                    dialog.style.cssText = 'width:min(100%,460px);background:#fff;color:#172033;border-radius:18px;padding:24px;box-shadow:0 24px 70px rgba(15,23,42,.28)';
                    const heading = document.createElement('h2'); heading.textContent = 'Similar FAQs found';
                    const message = document.createElement('p'); message.style.whiteSpace = 'pre-line'; message.textContent = `We found existing FAQs that may cover this question:\n\n${preview}\n\nContinue and prepare a new FAQ anyway?`;
                    const actions = document.createElement('div'); actions.style.cssText = 'display:flex;justify-content:flex-end;gap:10px;margin-top:22px';
                    const cancel = document.createElement('button'); cancel.type = 'button'; cancel.textContent = 'Cancel';
                    const yes = document.createElement('button'); yes.type = 'button'; yes.textContent = 'Continue anyway';
                    [cancel, yes].forEach(button => button.style.cssText = 'border:0;border-radius:9px;padding:10px 14px;cursor:pointer');
                    cancel.onclick = () => { backdrop.remove(); resolve(false); };
                    yes.onclick = () => { backdrop.remove(); resolve(true); };
                    actions.append(cancel, yes); dialog.append(heading, message, actions); backdrop.append(dialog); document.body.append(backdrop); cancel.focus();
                });
                if (!proceed) return;
            }
            form.dataset.similarityChecked = 'true';
            form.submit();
        } catch (error) {
            if (typeof window.showAlertModal === 'function') {
                window.showAlertModal({ title: 'Unable to check similar FAQs', text: error.message || 'Please try again.', icon: 'ph-light ph-warning-circle', variant: 'danger', confirmText: 'Got it', showCancel: false });
            } else {
                const backdrop = document.createElement('div');
                backdrop.style.cssText = 'position:fixed;inset:0;z-index:100000;display:flex;align-items:center;justify-content:center;padding:20px;background:rgba(15,23,42,.58)';
                const dialog = document.createElement('section'); dialog.setAttribute('role', 'alertdialog'); dialog.setAttribute('aria-modal', 'true');
                dialog.style.cssText = 'width:min(100%,440px);background:#fff;color:#172033;border-radius:18px;padding:24px;box-shadow:0 24px 70px rgba(15,23,42,.28)';
                const heading = document.createElement('h2'); heading.textContent = 'Unable to check similar FAQs';
                const message = document.createElement('p'); message.textContent = error.message || 'Please try again.';
                const ok = document.createElement('button'); ok.type = 'button'; ok.textContent = 'Got it'; ok.onclick = () => backdrop.remove();
                dialog.append(heading, message, ok); backdrop.append(dialog); document.body.append(backdrop); ok.focus();
            }
        } finally {
            if (submit && form.dataset.similarityChecked !== 'true') {
                submit.disabled = false;
                submit.textContent = 'Prepare FAQ draft';
            }
        }
    });
});
</script>
@endpush
