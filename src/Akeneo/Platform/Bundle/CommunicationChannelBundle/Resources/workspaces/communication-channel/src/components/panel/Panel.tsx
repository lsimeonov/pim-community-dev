import React from 'react';
import styled from 'styled-components';
import {useTranslate, useMediator} from '@akeneo-pim-community/legacy-bridge';
import {useAnnouncements} from './../../hooks/useAnnouncements';
import {AnnouncementFetcher} from './../../fetcher/announcement.type';
import {useCampaign} from './../../hooks/useCampaign';
import {CampaignFetcher} from './../../fetcher/campaign.type';
import {HeaderPanel} from './../../components/panel/Header';
import {AnnouncementComponent, EmptyAnnouncementList} from './announcement';
import {Announcement} from './../../models/announcement';

const ListAnnouncement = styled.ul`
  margin: 74px 30px 0 30px;
`;

type PanelDataProvider = {
  announcementFetcher: AnnouncementFetcher;
  campaignFetcher: CampaignFetcher;
};

type PanelProps = {
  dataProvider: PanelDataProvider
};

const Panel = ({dataProvider}: PanelProps): JSX.Element => {
  const __ = useTranslate();
  const mediator = useMediator();
  const {announcements} = useAnnouncements(dataProvider.announcementFetcher);
  const {campaign} = useCampaign(dataProvider.campaignFetcher);
  const closePanel = () => {
    mediator.trigger('communication-channel:panel:close');
  };
  const isSerenity = null !== campaign && 'serenity' === campaign.toLowerCase();

  return (
    <>
      <HeaderPanel title={__('akeneo_communication_channel.panel.title')} onClickCloseButton={closePanel} />
      {null !== announcements && isSerenity && (
        <ListAnnouncement>
          {announcements.map((announcement: Announcement, index: number): JSX.Element =>
            <AnnouncementComponent announcement={announcement} key={index} campaign={campaign} />)
          }
        </ListAnnouncement>
      )}
      {!isSerenity && (
        <EmptyAnnouncementList text={__('akeneo_communication_channel.panel.list.empty')} />
      )}
    </>
  );
};

export {Panel, PanelDataProvider};
